<?php

namespace App\Services;

use App\Exceptions\ApiException;

class GuestOrderEmailService
{
    private $pdo;
    private $transport;

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
        $url = 'https://www.importadorasupernova.com/StoreApi/v1/checkout/invitado/correo/pdf?token=' . $token;
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
        // Same hosting transport and sender already used for password recovery.
        $from = getenv('ORDER_MAIL_FROM') ?: 'no-reply@importadorasupernova.com';
        if (getenv('ORDER_MAIL_ENABLED') === '0' || !filter_var($from, FILTER_VALIDATE_EMAIL)
            || preg_match('/[\r\n]/', $from . $recipient)) return false;
        $headers = array('MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8',
            'From: Importadora Supernova <' . $from . '>');
        return mail($recipient, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
    }

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
        return '<!doctype html><html lang="es"><head><meta charset="UTF-8"></head><body style="margin:0;background:#f5f5f7;font-family:Arial,sans-serif;color:#30343b">'
            . '<div style="max-width:560px;margin:24px auto;background:white;border-radius:16px;overflow:hidden">'
            . '<div style="padding:24px;background:#f63876;color:white;font-size:22px;font-weight:bold">IMPORTADORA SUPERNOVA</div>'
            . '<div style="padding:24px;line-height:1.6"><h2>¡Tu pedido se ha registrado exitosamente!</h2>'
            . '<p>Hola, ' . $escape($name) . '. Gracias por comprar con nosotros.</p><p><strong>Número de orden: ' . $escape($reference) . '</strong></p>'
            . '<p>Hemos recibido tu pedido. Un asesor de ventas se pondrá en contacto contigo para darle seguimiento y proporcionarte la información necesaria para continuar con tu compra.</p>'
            . '<p><a style="display:inline-block;padding:12px 24px;background:#f63876;color:white;border-radius:8px;text-decoration:none;font-weight:bold" href="' . $escape($url) . '">Ver mi pedido</a></p>'
            . '<p><strong>Horario de atención:</strong><br>Lunes a sábado, de 9:00 a. m. a 5:00 p. m.</p>'
            . '<p>Si compraste fuera de este horario, te contactaremos durante nuestro siguiente horario de atención.</p>'
            . '<p>Este enlace privado estará disponible durante 30 días. Evita compartirlo: permite ver los datos de tu pedido.</p>'
            . '<p>¡Agradecemos tu confianza y preferencia!</p><p><strong>Importadora Supernova</strong><br><em>De todo para ti, a precios para ti.</em></p>'
            . '<a href="https://www.importadorasupernova.com">importadorasupernova.com</a></div></div></body></html>';
    }
}
