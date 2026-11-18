<?php
// Render a demonstration only; never creates an order, token or outbound message.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../src/Services/GuestOrderEmailService.php';
$root = dirname(__DIR__, 2);
$directory = $root . '/output/email';
if (!is_dir($directory)) mkdir($directory, 0755, true);
$html = \App\Services\GuestOrderEmailService::message('María Fernanda', 'INV-DEMO',
    'https://www.importadorasupernova.com/StoreApi/v1/checkout/invitado/correo/pdf?token=' . str_repeat('0', 64));
file_put_contents($directory . '/pedido-confirmacion.html', $html);
// Isolated offline copy for visual review without requesting remote images.
foreach (array('header-store.png', 'logo-white.png') as $asset) {
    $html = str_replace('https://www.importadorasupernova.com/StoreApi/assets/' . $asset,
        'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/../assets/' . $asset)), $html);
}
if (!is_dir($root . '/tmp/pdfs')) mkdir($root . '/tmp/pdfs', 0755, true);
file_put_contents($root . '/tmp/pdfs/email-preview-offline.html', $html);
echo "Created output/email/pedido-confirmacion.html (demonstration, no mail sent).\n";
