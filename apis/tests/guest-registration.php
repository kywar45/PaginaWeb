<?php
// Reuse the real registration fixture and run its regression checks as well.
require __DIR__ . '/registration.php';
require __DIR__ . '/../src/Repositories/OrderPdfRepository.php';
require __DIR__ . '/../src/Services/OrderPdfService.php';

class GuestRegisterPdo extends RegisterPdo {
    #[\ReturnTypeWillChange]
    public function prepare($sql, $options = array()) {
        return parent::prepare(str_replace('ON DUPLICATE KEY UPDATE folio_id = folio_id', 'ON CONFLICT(folio_id) DO NOTHING', $sql), $options);
    }
}
$pdo = new GuestRegisterPdo();
$pdo->exec("CREATE TABLE web_guest_registrations (folio_id INTEGER PRIMARY KEY, token_hash TEXT UNIQUE, expires_at TEXT, used_at TEXT, usuario_id INTEGER);
CREATE TABLE web_pedidos_envio (folio_id INTEGER PRIMARY KEY, nombre TEXT, apellido TEXT, correo TEXT, direccion TEXT, colonia TEXT, ciudad TEXT, estado TEXT, codigo_postal TEXT, telefono TEXT);
CREATE TABLE folios (id INTEGER PRIMARY KEY, id_usuario INTEGER, id_cliente_invitado INTEGER, orden TEXT, nombres TEXT, fecha TEXT, fecha_pago TEXT, total TEXT, estatus TEXT, shipping_carrier_id INTEGER, store_pickup INTEGER);
CREATE TABLE web_clientes_invitados (id INTEGER PRIMARY KEY, nombre TEXT, correo TEXT);
CREATE TABLE web_order_emails (id INTEGER PRIMARY KEY, folio_id INTEGER UNIQUE, cliente_id INTEGER, recipient TEXT, token_hash TEXT UNIQUE, message_html TEXT, subject TEXT, expires_at TEXT, sent_at TEXT, attempts INTEGER DEFAULT 0);
CREATE TABLE shipping_carriers (id INTEGER PRIMARY KEY, nombre TEXT, envio INTEGER);
CREATE TABLE admin_tiendas (id INTEGER PRIMARY KEY, nombre TEXT);
CREATE TABLE registro_usuario (id INTEGER PRIMARY KEY, id_usuario INTEGER, id_cliente_invitado INTEGER, id_producto INTEGER, nombre TEXT, codigo TEXT, precio TEXT, cantidad INTEGER, orden TEXT);
CREATE TABLE productos (id INTEGER PRIMARY KEY, preciou TEXT);
INSERT INTO shipping_carriers VALUES (1, 'Paquetería', 1);
INSERT INTO folios VALUES (42, NULL, 7, 'INV-7', 'Ana Pérez', '2026-11-19', NULL, '100', 'Pendiente', 1, NULL);
INSERT INTO web_clientes_invitados VALUES (7, 'Ana', ' ANA@EXAMPLE.COM ');
INSERT INTO web_pedidos_envio VALUES (42, 'Ana', 'Pérez', 'ana@example.com', 'Calle 1', 'Centro', 'Ciudad de México', 'Ciudad de México', '06050', '5512345678');
INSERT INTO registro_usuario VALUES (1, NULL, 7, 8, 'Gel', 'GEL-01', '100.00', 1, 'INV-7');
INSERT INTO productos VALUES (8, '120.00');");
// The checkout trims email before persisting it. Leave case differences for normalization checks.
$pdo->exec("UPDATE web_clientes_invitados SET correo = 'ANA@EXAMPLE.COM'");
$repo = new \App\Repositories\RegistrationRepository($pdo);
$mail = new RegisterMail();
$service = new \App\Services\RegistrationService($repo, new \App\Services\RegistrationEmailService($mail), new NoRegisterLimit());
$messages = array();
$email = new \App\Services\GuestOrderEmailService($pdo, function ($to, $subject, $html) use (&$messages) { $messages[] = $html; return true; });
$email->confirm(array('folio_id' => 42, 'cliente_invitado_id' => 7));
check(count($messages) === 1, 'Order email delivered using fake transport');
preg_match('/crear-cuenta-pedido\?token=([a-f0-9]{64})/', $messages[0], $match);
check(isset($match[1]), 'Order email offers password-only signup');
$token = $match[1];
check($pdo->query('SELECT token_hash FROM web_guest_registrations')->fetchColumn() === hash('sha256', $token), 'Separate invitation token is stored hashed');
check($pdo->query('SELECT token_hash FROM web_order_emails')->fetchColumn() !== hash('sha256', $token), 'PDF token cannot create accounts');
preg_match('/pedido\/correo\/pdf\?token=([a-f0-9]{64})/', $messages[0], $pdfToken);
foreach (array($pdfToken[1], str_repeat('f', 64)) as $invalidToken) {
    rejects(function () use ($service, $invalidToken) { $service->validateGuest(array('token' => $invalidToken)); }, 404);
}
check((int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn() === 0, 'Sending or opening invitation creates no account');
check($service->validateGuest(array('token' => $token)) === array('valid' => true), 'Read-only validation exposes no personal information');
$email->confirm(array('folio_id' => 42, 'cliente_invitado_id' => 7));
check(count($messages) === 1 && (int) $pdo->query('SELECT COUNT(*) FROM web_guest_registrations')->fetchColumn() === 1, 'Retry preserves invitation without duplicate email');
$payload = array('token' => $token, 'password' => 'correct-password', 'password_confirmation' => 'correct-password',
    'correo' => 'attacker@example.com', 'nombre' => 'Forged', 'user_vip' => 1, 'folio_id' => 99);
rejects(function () use ($service, $payload) { $service->registerGuest(array_replace($payload, array('password_confirmation' => 'different'))); }, 422);
$pdo->exec("UPDATE web_guest_registrations SET expires_at = '2000-01-01'");
rejects(function () use ($service, $payload) { $service->validateGuest($payload); }, 410);
rejects(function () use ($service, $payload) { $service->registerGuest($payload); }, 410);
$pdo->exec("UPDATE web_guest_registrations SET expires_at = '2099-01-01'");
// Fail after the user INSERT: both the account and claim must roll back.
$pdo->exec("CREATE TRIGGER fail_claim BEFORE UPDATE ON web_guest_registrations BEGIN SELECT RAISE(ABORT, 'test rollback'); END");
try { $service->registerGuest($payload); throw new RuntimeException('Expected rollback'); }
catch (PDOException $error) {}
check((int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn() === 0 && $service->validateGuest($payload)['valid'], 'Failed claim leaves no account and invitation remains reusable');
$pdo->exec('DROP TRIGGER fail_claim');
check($service->registerGuest($payload)['verified'], 'Creating account also confirms ownership of emailed link');
$user = $repo->findEmail('ana@example.com');
check($user['nombre'] === 'Ana' && password_verify('correct-password', $user['pass']), 'Uses order snapshot and hashes password');
check($user['token_registro'] !== '' && (int) $pdo->query('SELECT user_vip FROM usuario')->fetchColumn() === 0, 'Confirmed normal account ignores forged privileges');
check($pdo->query('SELECT codigop FROM usuario')->fetchColumn() === '06050', 'Stored delivery data preserved');
rejects(function () use ($service, $payload) { $service->registerGuest($payload); }, 410);
check((int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn() === 1, 'Single-use token cannot duplicate account');
$users = new \App\Repositories\UserRepository($pdo);
$id = (int) $user['id'];
check($users->countOrders($id) === 1 && $users->countPaidOrders($id) === 0 && count($users->ordersPage($id, 10, 0)) === 1, 'Only this order is linked, without inventing a payment');
check($users->countOrders($id + 1) === 0, 'Foreign account cannot see linked order');
$pdfRepo = new \App\Repositories\OrderPdfRepository($pdo);
$pdf = new \App\Services\OrderPdfService($pdfRepo);
check($pdf->prepare(42, $id)['piezas'] === 1 && $pdfRepo->findOwned(42, $id + 1) === null, 'Account PDF preserves guest lines and ownership');
check($pdf->prepareGuest(42, 7)['piezas'] === 1, 'Original guest PDF remains available');
check($pdo->query('SELECT id_usuario FROM folios')->fetchColumn() === null, 'Historical order ownership fields remain unchanged');
// A second order with that mailbox gets no signup invitation.
$pdo->exec("INSERT INTO folios SELECT 43, NULL, 7, 'INV-8', nombres, fecha, fecha_pago, total, estatus, shipping_carrier_id, store_pickup FROM folios WHERE id = 42");
$email->confirm(array('folio_id' => 43, 'cliente_invitado_id' => 7));
check(strpos($messages[1], 'crear-cuenta-pedido') === false && strpos($messages[1], 'Iniciar sesión') !== false, 'Existing account gets login CTA without signup token');
// Mailbox registers after invitation was sent: both validation and final submission reject it.
$pdo->exec("INSERT INTO folios SELECT 44, NULL, 7, 'INV-9', nombres, fecha, fecha_pago, total, estatus, shipping_carrier_id, store_pickup FROM folios WHERE id = 42;
INSERT INTO web_pedidos_envio SELECT 44, nombre, apellido, 'new@example.com', direccion, colonia, ciudad, estado, codigo_postal, telefono FROM web_pedidos_envio WHERE folio_id = 42");
$url = $repo->inviteGuest(44, 'new@example.com');
parse_str(parse_url($url, PHP_URL_QUERY), $query);
$pdo->exec("UPDATE usuario SET correo = ' NEW@EXAMPLE.COM '");
check(rejects(function () use ($service, $query) { $service->validateGuest($query); }, 409) === 'EMAIL_EXISTS', 'Newly registered mailbox is caught when link opens');
$before = $pdo->query('SELECT * FROM usuario')->fetchAll(PDO::FETCH_ASSOC);
check(rejects(function () use ($service, $query) { $service->registerGuest(array_merge($query, array('password' => 'other-password', 'password_confirmation' => 'other-password'))); }, 409) === 'EMAIL_EXISTS', 'Final transaction repeats normalized email uniqueness check');
check($pdo->query('SELECT * FROM usuario')->fetchAll(PDO::FETCH_ASSOC) === $before, 'Existing account password and data are untouched');
echo "Guest registration checks passed: email invitations, expiry, single use, atomic rollback, duplicate accounts, trusted data, linked order and PDFs.\n";
