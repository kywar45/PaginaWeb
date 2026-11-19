<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../src/Services/RegistrationEmailService.php';
$root = dirname(__DIR__, 2);
$directory = $root . '/output/email';
if (!is_dir($directory)) mkdir($directory, 0755, true);
$html = \App\Services\RegistrationEmailService::message('María Fernanda',
    'https://www.importadorasupernova.com/ImportadoraSupernova/confirmar-correo?token=' . str_repeat('0', 64));
file_put_contents($directory . '/registro-confirmacion.html', $html);
echo "Created demonstration output/email/registro-confirmacion.html; no email sent.\n";
