<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../src/Services/PasswordResetEmailService.php';
$root = dirname(__DIR__, 2);
$directory = $root . '/output/email';
if (!is_dir($directory)) mkdir($directory, 0755, true);
$html = \App\Services\PasswordResetEmailService::message('María Fernanda',
    'https://www.importadorasupernova.com/ImportadoraSupernova/restablecer-contrasena?token=' . str_repeat('0', 64));
file_put_contents($directory . '/recuperar-contrasena.html', $html);
echo "Created demonstration output/email/recuperar-contrasena.html; no email sent.\n";
