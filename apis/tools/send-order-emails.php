<?php
// CLI only: pass the existing StoreApi bootstrap that loads config/autoloading, not public/index.php.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$bootstrap = $argv[1] ?? '';
if (!$bootstrap || !is_file($bootstrap)) { fwrite(STDERR, "Usage: php send-order-emails.php /absolute/path/to/StoreApi/bootstrap.php\n"); exit(1); }
require $bootstrap;
$pdo = \App\Database\Connection::get();
$service = new \App\Services\GuestOrderEmailService($pdo);
// Recover confirmations not queued because an earlier notification step failed.
$missing = $pdo->query('SELECT f.id AS folio_id, f.id_cliente_invitado AS cliente_invitado_id
    FROM folios f LEFT JOIN web_order_emails e ON e.folio_id = f.id
    WHERE e.id IS NULL AND f.id_usuario IS NULL AND f.id_cliente_invitado IS NOT NULL
      AND f.fecha >= DATE_SUB(NOW(), INTERVAL 1 DAY)
      AND EXISTS (SELECT 1 FROM registro_usuario r WHERE r.orden = f.orden AND r.id_cliente_invitado = f.id_cliente_invitado AND r.id_usuario IS NULL)
    ORDER BY f.id LIMIT 20')->fetchAll(\PDO::FETCH_ASSOC);
foreach ($missing as $order) {
    try { $service->confirm($order); }
    catch (\Throwable $error) { error_log('Order email queue recovery failed for folio ' . (int) $order['folio_id']); }
}
$pending = $pdo->query('SELECT folio_id FROM web_order_emails WHERE sent_at IS NULL AND expires_at > NOW() AND attempts < 10 ORDER BY id LIMIT 20')->fetchAll(\PDO::FETCH_COLUMN);
foreach ($pending as $id) {
    try { $service->deliver((int) $id); }
    catch (\Throwable $error) { error_log('Pending order email failed for folio ' . (int) $id); }
}
