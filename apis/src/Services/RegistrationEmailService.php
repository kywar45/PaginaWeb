<?php
namespace App\Services;

class RegistrationEmailService
{
    private $transport;
    public function __construct(GuestOrderEmailService $transport) { $this->transport = $transport; }
    public function send($name, $email, $token)
    {
        $url = 'https://www.importadorasupernova.com/ImportadoraSupernova/confirmar-correo?token=' . $token;
        return $this->transport->sendTransactional($email, 'Confirma tu cuenta | Importadora Supernova', self::message($name, $url));
    }
    public static function message($name, $url)
    {
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Confirma tu cuenta Supernova</title></head>
<body style="margin:0;background:#f5f3f5;font-family:Arial,Helvetica,sans-serif;color:#312630;">
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">Un paso más para disfrutar de tu cuenta: confirma tu correo electrónico.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:24px 12px;">
<!--[if mso]><table role="presentation" width="580"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;background:#fff;border:1px solid #ebe4e9;border-radius:16px;overflow:hidden;">
<tr><td align="center" bgcolor="#ee356b" background="https://www.importadorasupernova.com/StoreApi/assets/header-store.png" style="padding:30px 24px;background-color:#ee356b;background-image:url('https://www.importadorasupernova.com/StoreApi/assets/header-store.png');background-size:cover;background-position:center;">
<img src="https://www.importadorasupernova.com/StoreApi/assets/logo-white.png" width="210" alt="Importadora Supernova" style="display:block;width:210px;max-width:100%;height:auto;color:white;font-size:20px;font-weight:bold;"><p style="color:#fff;font-size:12px;margin:12px 0 0;">De todo para ti, a precios para ti.</p></td></tr>
<tr><td style="padding:30px 26px;">
<p style="margin:0 0 12px;color:#d62d61;font-size:11px;font-weight:bold;letter-spacing:1.5px;">BIENVENIDO A SUPERNOVA</p>
<h1 style="font-size:28px;line-height:1.2;margin:0 0 20px;">Tu cuenta está a un paso.</h1>
<p style="font-size:14px;line-height:23px;">Hola, <strong>{$name}</strong>:</p>
<p style="font-size:14px;line-height:23px;color:#796c76;">Gracias por registrarte. Confirma tu correo para activar el acceso a tu cuenta, consultar tus pedidos y administrar tus datos de entrega.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;"><tr><td align="center" bgcolor="#ec326b" style="border-radius:9px;">
<a href="{$url}" target="_blank" style="display:block;padding:16px 20px;color:#fff;font-weight:bold;font-size:14px;text-decoration:none;">Confirmar mi correo &#8594;</a></td></tr></table>
<p style="font-size:12px;line-height:20px;color:#796c76;">El enlace vence en <strong>24 horas</strong>. Hasta confirmar tu correo, no podrás iniciar sesión.</p>
<p style="font-size:12px;line-height:20px;color:#796c76;">Si el botón no abre, <a href="{$url}" target="_blank" style="color:#d62d61;">confirma tu cuenta desde este enlace</a>.</p>
<p style="border-top:1px solid #eee5ea;padding-top:18px;margin-top:24px;font-size:11px;line-height:19px;color:#958690;">Si no solicitaste esta cuenta, ignora este mensaje. No compartas este enlace ni tu contraseña.</p>
</td></tr><tr><td align="center" style="padding:18px;border-top:1px solid #f0e9ee;"><strong style="font-size:12px;">Importadora Supernova</strong><br><a href="https://www.importadorasupernova.com/" style="font-size:11px;line-height:24px;color:#958690;text-decoration:none;">importadorasupernova.com</a></td></tr>
</table><!--[if mso]></td></tr></table><![endif]--></td></tr></table></body></html>
HTML;
    }
}
