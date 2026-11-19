<?php
require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/GuestOrderEmailService.php';
require __DIR__ . '/../src/Repositories/RegistrationRepository.php';
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
class EmailPdo extends PDO {
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqliteCreateFunction('GET_LOCK', function () { return 1; });
        $this->sqliteCreateFunction('RELEASE_LOCK', function () { return 1; });
    }
    #[\ReturnTypeWillChange]
    public function prepare($query, $options = array()) {
        return parent::prepare(str_replace('ON DUPLICATE KEY UPDATE folio_id = folio_id', 'ON CONFLICT(folio_id) DO NOTHING', $query), $options);
    }
}
$pdo = new EmailPdo();
$pdo->exec("CREATE TABLE web_order_emails (id INTEGER PRIMARY KEY, folio_id INTEGER UNIQUE, cliente_id INTEGER, recipient TEXT, token_hash TEXT UNIQUE, message_html TEXT, subject TEXT, expires_at TEXT, sent_at TEXT, attempts INTEGER DEFAULT 0);
CREATE TABLE usuario (id INTEGER PRIMARY KEY, nombre TEXT, correo TEXT, pass TEXT, token_registro TEXT);
CREATE TABLE web_guest_registrations (folio_id INTEGER PRIMARY KEY, token_hash TEXT UNIQUE, expires_at TEXT, used_at TEXT, usuario_id INTEGER);
CREATE TABLE folios (id INTEGER, id_cliente_invitado INTEGER, id_usuario INTEGER, orden TEXT);
CREATE TABLE web_clientes_invitados (id INTEGER, correo TEXT, nombre TEXT);
INSERT INTO folios VALUES (42, 7, NULL, 'INV-7'), (43, 7, 10, 'OTHER');
INSERT INTO web_clientes_invitados VALUES (7, 'real@example.com', '<script>');");
$sent = array(); $accept = false;
$service = new \App\Services\GuestOrderEmailService($pdo, function ($to, $subject, $body) use (&$sent, &$accept) {
    $sent[] = array($to, $subject, $body); return $accept;
});
$order = array('folio_id' => 42, 'cliente_invitado_id' => 7);
check($service->confirm($order) === 'pending', 'Rejected transport leaves mail pending');
$accept = true;
check($service->confirm($order) === 'sent', 'Retry sends existing confirmation');
check($sent[0] === $sent[1], 'Retry retains same private link and recipient');
check($service->confirm($order) === 'sent' && count($sent) === 2, 'Saved confirmation is not sent twice');
check($sent[0][0] === 'real@example.com' && strpos($sent[0][2], '<script>') === false, 'Stored recipient and escaped customer data');
preg_match('/token=([a-f0-9]{64})/', $sent[0][2], $match);
check(strpos($sent[0][2], '/ImportadoraSupernova/pedido/correo/pdf?token=') !== false, 'Email points to the website PDF viewer');
check(strpos($sent[0][2], '/StoreApi/v1/checkout/') === false, 'Order links in email do not expose API URL');
preg_match_all('/<a\b[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/s', $sent[0][2], $links, PREG_SET_ORDER);
$receiptLinks = array_values(array_filter($links, function ($link) use ($match) { return strpos($link[1], 'token=' . $match[1]) !== false; }));
check(count($receiptLinks) === 3, 'Order number, PDF button and fallback all use the same private receipt link');
check(strpos($receiptLinks[0][2], 'INV-7') !== false && strpos($receiptLinks[1][2], 'Ver mi pedido en PDF') !== false, 'Both the order reference and CTA open the PDF');
check(strpos($sent[0][2], 'no acredita su pago') !== false, 'Receipt never claims payment confirmation');
unset($_COOKIE['sn_guest_cart']);
check($service->resolve($match[1]) === array('folio_id' => 42, 'cliente_id' => 7), 'Private email link works without checkout cookie');
check($pdo->query('SELECT token_hash FROM web_order_emails')->fetchColumn() !== $match[1], 'Lookup token stored hashed');
foreach (array('garbage', str_repeat('a', 64)) as $bad) {
    try { $service->resolve($bad); throw new RuntimeException('Accepted invalid link'); }
    catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === 404, 'Bad link rejected'); }
}
$pdo->exec("UPDATE web_order_emails SET expires_at = '2000-01-01 00:00:00'");
try { $service->resolve($match[1]); throw new RuntimeException('Accepted expired link'); }
catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === 410, 'Expired link rejected'); }
try { $service->confirm(array('folio_id' => 43, 'cliente_invitado_id' => 7)); throw new LogicException('Accepted account order'); }
catch (RuntimeException $error) { check($error->getMessage() === 'Invalid order email recipient', 'Account order rejected'); }
echo "Order email checks passed: stored recipient, escaped HTML, private link, expiry, deduplication and retry.\n";
