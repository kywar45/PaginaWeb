<?php

namespace App\Services;

use App\Exceptions\ApiException;

class GuestOrderEmailService
{
    private $pdo;
    private $transport;
    private $diagnostic;

    public function __construct(\PDO $pdo, callable $transport = null)
    {
        $this->pdo = $pdo;
        $this->transport = $transport;
    }

    // Run only after the order transaction has committed. One confirmation per folio.
    public function confirm(array $order)
    {
        $query = $this->pdo->prepare('SELECT f.id, f.id_cliente_invitado, f.orden, c.correo, c.nombre
            FROM folios f JOIN web_clientes_invitados c ON c.id = f.id_cliente_invitado
            WHERE f.id = ? AND f.id_usuario IS NULL AND f.id_cliente_invitado = ?');
        $query->execute(array($order['folio_id'], $order['cliente_invitado_id']));
        $row = $query->fetch(\PDO::FETCH_ASSOC);
        if (!$row || !filter_var($row['correo'], FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Invalid order email recipient');
        $token = bin2hex(random_bytes(32));
        $url = 'https://www.importadorasupernova.com/ImportadoraSupernova/pedido/correo/pdf?token=' . $token;
        $subject = 'Pedido registrado ' . $row['orden'] . ' | Importadora Supernova';
        $html = self::message($row['nombre'], $row['orden'], $url);
        $insert = $this->pdo->prepare('INSERT INTO web_order_emails
            (folio_id, cliente_id, recipient, token_hash, message_html, subject, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE folio_id = folio_id');
        $insert->execute(array($row['id'], $row['id_cliente_invitado'], $row['correo'], hash('sha256', $token), $html, $subject, date('Y-m-d H:i:s', time() + 30 * 86400)));
        return $this->deliver((int) $row['id']);
    }

    public function deliver($folioId)
    {
        // Serialize sends, including retries. A crash after provider acceptance can still cause a retry.
        $name = 'sn:order-email:' . (int) $folioId;
        $lock = $this->pdo->prepare('SELECT GET_LOCK(?, 0)');
        $lock->execute(array($name));
        if ((int) $lock->fetchColumn() !== 1) return 'pending';
        try {
            $query = $this->pdo->prepare('SELECT * FROM web_order_emails WHERE folio_id = ?');
            $query->execute(array($folioId));
            $row = $query->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return 'pending';
            if ($row['sent_at'] !== null) return 'sent';
            if (strtotime($row['expires_at']) <= time()) return 'expired';
            $this->pdo->prepare('UPDATE web_order_emails SET attempts = attempts + 1 WHERE id = ?')->execute(array($row['id']));
            $accepted = $this->transport
                ? call_user_func($this->transport, $row['recipient'], $row['subject'], $row['message_html'])
                : $this->send($row['recipient'], $row['subject'], $row['message_html']);
            if (!$accepted) return 'pending';
            $this->pdo->prepare('UPDATE web_order_emails SET sent_at = ?, message_html = ? WHERE id = ?')
                ->execute(array(date('Y-m-d H:i:s'), '', $row['id']));
            return 'sent';
        } finally {
            $this->pdo->prepare('SELECT RELEASE_LOCK(?)')->execute(array($name));
        }
    }

    private function send($recipient, $subject, $html)
    {
        $from = self::setting('ORDER_MAIL_FROM', 'no-reply@importadorasupernova.com');
        if (!self::enabled('ORDER_MAIL_ENABLED', true)) return $this->record('MAIL_DISABLED', false);
        if (!is_string($from) || !filter_var($from, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $from)) return $this->record('MAIL_FROM_INVALID', false);
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $recipient)) return $this->record('MAIL_RECIPIENT_INVALID', false);
        if (!function_exists('mail')) return $this->record('MAIL_UNAVAILABLE', false);
        $headers = array('MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8',
            'From: Importadora Supernova <' . $from . '>');
        $warnings = array();
        set_error_handler(function ($severity, $message) use (&$warnings) { $warnings[] = $message; return true; }, E_WARNING | E_NOTICE | E_USER_WARNING);
        try {
            $accepted = mail($recipient, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
            return $this->record($accepted ? 'MAIL_ACCEPTED' : 'MAIL_REJECTED', (bool) $accepted, implode(' | ', $warnings));
        } catch (\Throwable $error) {
            return $this->record('MAIL_EXCEPTION', false, get_class($error) . ': ' . $error->getMessage());
        } finally { restore_error_handler(); }
    }

    public static function setting($name, $default = null)
    {
        if (function_exists('api_env')) return \api_env($name, $default);
        $value = getenv($name);
        return $value !== false ? $value : ($_ENV[$name] ?? $_SERVER[$name] ?? $default);
    }

    public static function enabled($name, $default = false)
    {
        return in_array(strtolower((string) self::setting($name, $default ? '1' : '0')), array('1', 'true', 'yes', 'on'), true);
    }

    private function record($code, $accepted, $detail = '')
    {
        $messages = array(
            'MAIL_ACCEPTED' => 'PHP aceptó el mensaje. Revisa la bandeja de entrada y spam; esto no confirma su entrega.',
            'MAIL_DISABLED' => 'El envío está desactivado por ORDER_MAIL_ENABLED.',
            'MAIL_FROM_INVALID' => 'ORDER_MAIL_FROM no contiene un remitente válido.',
            'MAIL_RECIPIENT_INVALID' => 'El correo de destino no es válido.',
            'MAIL_UNAVAILABLE' => 'La función mail() no está disponible en este servidor.',
            'MAIL_REJECTED' => 'mail() rechazó el mensaje. Consulta el registro con esta referencia para ver el motivo del servidor.',
            'MAIL_EXCEPTION' => 'Ocurrió un error en el transporte. Consulta el registro con esta referencia.'
        );
        $this->diagnostic = array('accepted' => $accepted, 'code' => $code,
            'message' => $messages[$code], 'reference' => bin2hex(random_bytes(8)),
            'transport' => 'php_mail', 'from' => self::setting('ORDER_MAIL_FROM', 'no-reply@importadorasupernova.com'),
            'automatic_enabled' => self::enabled('ORDER_MAIL_AUTO_ENABLED', true));
        // No recipient, message body, private PDF token or credentials in logs.
        self::log($this->diagnostic['reference'] . ' ' . $code . ($detail !== '' ? ' ' . $detail : ''));
        return $accepted;
    }

    public static function log($message)
    {
        $line = '[' . date('c') . '] OrderMail ' . substr(str_replace(array("\r", "\n"), ' ', $message), 0, 1800);
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (is_dir($directory) && is_writable($directory)) error_log($line . PHP_EOL, 3, $directory . '/order-mail.log');
        else error_log($line);
    }

    public function test(array $data)
    {
        if (!self::enabled('ORDER_MAIL_TEST_ENABLED')) throw new ApiException('MAIL_TEST_DISABLED', 'La prueba está desactivada. Habilita ORDER_MAIL_TEST_ENABLED=1 en la configuración privada del API.', 403);
        $email = $data['correo'] ?? null;
        $name = $data['nombre'] ?? '';
        if (!is_string($email) || strlen($email) > 150 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
            throw new ApiException('VALIDATION_ERROR', 'Ingresa un correo válido en los datos del pedido.', 422);
        }
        if (!is_string($name) || strlen($name) > 400 || preg_match('/[\x00-\x1F\x7F]/', $name) || !preg_match('//u', $name)) {
            throw new ApiException('VALIDATION_ERROR', 'Revisa el nombre del destinatario.', 422);
        }
        $guest = (new CartIdentity())->existingGuest();
        if ($guest === null) throw new ApiException('GUEST_SESSION_REQUIRED', 'Abre tu carrito antes de enviar la prueba.', 401);
        $this->testBudget($guest['guest_hash'], strtolower(trim($email)));
        $escape = function ($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); };
        $body = '<!doctype html><html lang="es"><meta charset="UTF-8"><body style="font-family:Arial,sans-serif">'
            . '<h2 style="color:#ed326b">Prueba de correo · Supernova</h2><p>Hola, ' . $escape(trim($name) ?: 'cliente') . '.</p>'
            . '<p>Este mensaje comprueba el envío al correo capturado en tu pedido. No registra una compra ni confirma un pago.</p>'
            . '<p>Si lo recibiste, el transporte de correo funciona para esta dirección.</p></body></html>';
        $subject = 'Prueba de correo | Importadora Supernova';
        if ($this->transport) {
            try { $accepted = (bool) call_user_func($this->transport, trim($email), $subject, $body); }
            catch (\Throwable $error) { $this->record('MAIL_EXCEPTION', false, get_class($error)); return $this->diagnostic; }
            $this->record($accepted ? 'MAIL_ACCEPTED' : 'MAIL_REJECTED', $accepted);
        } else $this->send(trim($email), $subject, $body);
        return $this->diagnostic;
    }

    protected function testBudget($guestHash, $recipient)
    {
        // Private diagnostic mode still needs a persistent bound on outbound mail.
        $directory = $this->testBudgetDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new ApiException('MAIL_TEST_STORAGE', 'No se pudo preparar el límite de pruebas. Revisa permisos de storage/mail-test.', 503);
        }
        $file = @fopen($directory . '/limits.json', 'c+');
        if (!$file) throw new ApiException('MAIL_TEST_STORAGE', 'No se pudo abrir el registro de pruebas. Revisa permisos de storage/mail-test.', 503);
        try {
            if (!flock($file, LOCK_EX | LOCK_NB)) throw new ApiException('MAIL_TEST_BUSY', 'Hay otra prueba en curso. Intenta nuevamente.', 429);
            $raw = stream_get_contents($file);
            $entries = $raw === '' ? array() : json_decode($raw, true);
            if (!is_array($entries)) throw new ApiException('MAIL_TEST_STORAGE', 'El registro de pruebas necesita revisión.', 503);
            $now = time();
            $entries = array_values(array_filter($entries, function ($entry) use ($now) { return is_array($entry) && ($entry['at'] ?? 0) > $now - 3600; }));
            $ip = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $recipient = hash('sha256', $recipient);
            $sameIp = $sameRecipient = 0;
            foreach ($entries as $entry) {
                if (($entry['guest'] ?? '') === $guestHash && $entry['at'] > $now - 60) throw new ApiException('MAIL_TEST_RATE_LIMIT', 'Espera un minuto entre pruebas.', 429);
                if (($entry['ip'] ?? '') === $ip) $sameIp++;
                if (($entry['recipient'] ?? '') === $recipient) $sameRecipient++;
            }
            if (count($entries) >= 20 || $sameIp >= 5 || $sameRecipient >= 3) throw new ApiException('MAIL_TEST_RATE_LIMIT', 'Se alcanzó el límite de pruebas por hora. Revisa el correo o intenta más tarde.', 429);
            $entries[] = array('at' => $now, 'ip' => $ip, 'guest' => $guestHash, 'recipient' => $recipient);
            $json = json_encode($entries);
            if (!ftruncate($file, 0) || !rewind($file) || fwrite($file, $json) !== strlen($json) || !fflush($file)) throw new ApiException('MAIL_TEST_STORAGE', 'No se pudo registrar la prueba.', 503);
        } finally { flock($file, LOCK_UN); fclose($file); }
    }

    protected function testBudgetDirectory() { return dirname(__DIR__, 2) . '/storage/mail-test'; }

    public function resolve($token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) $this->invalid();
        $query = $this->pdo->prepare('SELECT folio_id, cliente_id, expires_at FROM web_order_emails WHERE token_hash = ?');
        $query->execute(array(hash('sha256', $token)));
        $row = $query->fetch(\PDO::FETCH_ASSOC);
        if (!$row) $this->invalid();
        if (strtotime($row['expires_at']) <= time()) throw new ApiException('ORDER_LINK_EXPIRED', 'Este enlace ha vencido. Contacta a soporte para consultar tu pedido.', 410);
        return array('folio_id' => (int) $row['folio_id'], 'cliente_id' => (int) $row['cliente_id']);
    }

    private function invalid() { throw new ApiException('ORDER_LINK_INVALID', 'El enlace del pedido no es válido.', 404); }

    public static function message($name, $reference, $url)
    {
        $escape = function ($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); };
        $name = $escape($name);
        $reference = $escape($reference);
        $url = $escape($url);
        // Presentation tables and inline styles keep the receipt usable in email clients.
        // Solid pink remains visible when a client blocks remote decorative images.
        return <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Pedido {$reference} | Importadora Supernova</title>
  <style>
    @media only screen and (max-width: 600px) {
      .email-outer { padding: 16px 10px !important; }
      .email-content { padding: 26px 22px !important; }
      .email-title { font-size: 27px !important; }
      .email-order { font-size: 24px !important; }
      .email-button { display: block !important; padding: 16px 18px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f5f3f5;font-family:Arial,Helvetica,sans-serif;color:#312630;-webkit-text-size-adjust:100%;">
  <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Recibimos tu pedido {$reference}. Consulta tu comprobante y conoce los siguientes pasos.</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f5f3f5">
    <tr><td class="email-outer" align="center" style="padding:32px 16px;">
      <!--[if mso]><table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:#ffffff;border:1px solid #ebe4e9;border-radius:18px;overflow:hidden;">
        <tr><td align="center" bgcolor="#ee356b" background="https://www.importadorasupernova.com/StoreApi/assets/header-store.png" style="padding:30px 24px;background-color:#ee356b;background-image:url('https://www.importadorasupernova.com/StoreApi/assets/header-store.png');background-position:center;background-size:cover;border-radius:18px 18px 0 0;">
          <a href="https://www.importadorasupernova.com/" target="_blank" style="color:#ffffff;text-decoration:none;">
            <img src="https://www.importadorasupernova.com/StoreApi/assets/logo-white.png" width="210" alt="Importadora Supernova" border="0" style="display:block;width:210px;max-width:100%;height:auto;color:#ffffff;font-size:21px;font-weight:bold;">
          </a>
          <p style="margin:12px 0 0;color:#ffffff;font-size:12px;line-height:18px;">De todo para ti, a precios para ti.</p>
        </td></tr>
        <tr><td class="email-content" style="padding:32px 36px;">
          <p style="margin:0 0 12px;color:#d62d61;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;">CONFIRMACIÓN DE PEDIDO</p>
          <h1 class="email-title" style="margin:0 0 18px;font-size:31px;line-height:1.18;letter-spacing:-0.7px;font-weight:700;color:#312630;">Gracias por elegir<br>Supernova.</h1>
          <p style="margin:0 0 8px;font-size:14px;line-height:23px;">Hola, <strong>{$name}</strong>:</p>
          <p style="margin:0 0 24px;font-size:14px;line-height:23px;color:#796c76;">Recibimos tu pedido correctamente. Nuestro equipo te acompañará con los siguientes pasos de tu compra.</p>

          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #ecdfe5;border-radius:12px;">
            <tr><td style="padding:20px 22px;">
              <p style="margin:0 0 8px;color:#877780;font-size:11px;line-height:16px;font-weight:bold;letter-spacing:0.8px;">TU NÚMERO DE ORDEN</p>
              <a class="email-order" href="{$url}" target="_blank" style="font-size:28px;line-height:36px;font-weight:bold;letter-spacing:-0.5px;color:#df2f65;text-decoration:none;overflow-wrap:anywhere;">{$reference} &#8599;</a>
              <p style="margin:8px 0 0;font-size:12px;line-height:19px;color:#796c76;">Pedido registrado &middot; Pendiente de seguimiento</p>
            </td></tr>
          </table>
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr><td align="center" style="padding:22px 0 6px;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr><td align="center" bgcolor="#ec326b" style="border-radius:9px;background-color:#ec326b;">
                  <a class="email-button" href="{$url}" target="_blank" style="display:block;padding:16px 24px;border:1px solid #ec326b;border-radius:9px;font-size:14px;line-height:20px;font-weight:bold;color:#ffffff;text-decoration:none;text-align:center;">Ver mi pedido en PDF &#8594;</a>
                </td></tr>
              </table>
            </td></tr>
          </table>
          <p style="margin:4px 0 27px;text-align:center;font-size:11px;line-height:18px;color:#8e808a;">También puedes abrir tu comprobante haciendo clic en el número de orden.</p>

          <h2 style="margin:0 0 16px;font-size:17px;line-height:24px;color:#312630;">¿Qué sigue ahora?</h2>
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
              <td width="32" valign="top" style="padding:0 12px 16px 0;"><span style="display:block;width:28px;height:28px;line-height:28px;border-radius:14px;background-color:#f9eaf0;color:#cc2b59;text-align:center;font-size:12px;font-weight:bold;">1</span></td>
              <td style="padding:0 0 16px;"><strong style="font-size:13px;line-height:20px;">Revisamos tu pedido</strong><br><span style="font-size:12px;line-height:20px;color:#796c76;">Nuestro equipo de ventas recibe la información de tu compra.</span></td>
            </tr>
            <tr>
              <td width="32" valign="top" style="padding:0 12px 0 0;"><span style="display:block;width:28px;height:28px;line-height:28px;border-radius:14px;background-color:#f9eaf0;color:#cc2b59;text-align:center;font-size:12px;font-weight:bold;">2</span></td>
              <td><strong style="font-size:13px;line-height:20px;">Un asesor se pone en contacto contigo</strong><br><span style="font-size:12px;line-height:20px;color:#796c76;">Te ayudará con el seguimiento y la información para continuar.</span></td>
            </tr>
          </table>
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;background-color:#f8f6f8;border-radius:10px;">
            <tr><td style="padding:17px 20px;">
              <p style="margin:0 0 4px;font-size:12px;line-height:20px;font-weight:bold;">Estamos para ayudarte</p>
              <p style="margin:0;font-size:12px;line-height:20px;color:#796c76;">Lunes a sábado &middot; 9:00 a. m. a 5:00 p. m.<br>Si compraste fuera de este horario, te contactaremos en nuestro siguiente horario de atención.</p>
              <p style="margin:10px 0 0;font-size:12px;line-height:20px;"><a href="tel:+525524954670" style="color:#d62d61;text-decoration:none;font-weight:bold;">55 2495 4670</a></p>
            </td></tr>
          </table>
          <p style="margin:22px 0 0;font-size:11px;line-height:18px;color:#958690;">Tu enlace es privado y está disponible durante 30 días. Evita compartirlo: permite consultar los datos de tu pedido. Este correo confirma el registro del pedido; no acredita su pago.</p>
          <p style="margin:12px 0 0;font-size:11px;line-height:18px;color:#958690;">Si el botón no abre, <a href="{$url}" target="_blank" style="color:#c72d5a;text-decoration:underline;">consulta aquí el comprobante de tu pedido</a>.</p>
        </td></tr>
        <tr><td align="center" style="padding:20px 24px;border-top:1px solid #f0e9ee;">
          <p style="margin:0 0 5px;font-size:12px;line-height:19px;color:#51414c;font-weight:bold;">Importadora Supernova</p>
          <a href="https://www.importadorasupernova.com/" target="_blank" style="font-size:11px;line-height:18px;color:#a18f9b;text-decoration:none;">importadorasupernova.com</a>
        </td></tr>
      </table>
      <!--[if mso]></td></tr></table><![endif]-->
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
