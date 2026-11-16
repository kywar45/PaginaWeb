<?php
namespace App\Http {
    class Response {
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
        public static function success($data, $status) { return array('status' => $status, 'data' => $data); }
    }
    class Request { public static function json() { return array(); } }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Services/OrderPdfLink.php';
    require __DIR__ . '/../src/Services/GuestOrderPdfLink.php';
    require __DIR__ . '/../src/Repositories/OrderPdfRepository.php';
    require __DIR__ . '/../src/Services/OrderPdfService.php';
    require __DIR__ . '/../src/Services/GuestOrderService.php';
    require __DIR__ . '/../src/Controllers/GuestOrderController.php';
    require __DIR__ . '/../src/Controllers/GuestOrderPdfController.php';
    require __DIR__ . '/../src/Pdf/OrderPdf.php';
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    class TestGuestOrder extends \App\Services\GuestOrderService {
        public function __construct() {}
        public function create(array $data) { return array('folio_id' => 42, 'cliente_invitado_id' => 7, 'orden' => 'INV-7', 'productos_registrados' => 1); }
    }
    $pdo = new \PDO('sqlite::memory:');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE web_clientes_invitados (id INTEGER PRIMARY KEY, correo TEXT);
        INSERT INTO web_clientes_invitados VALUES (7,'guest@example.com'), (8,'foreign@example.com');
        CREATE TABLE shipping_carriers (id INTEGER PRIMARY KEY, nombre TEXT, envio INTEGER);
        INSERT INTO shipping_carriers VALUES (21,'Entrega personal',0);
        CREATE TABLE admin_tiendas (id INTEGER PRIMARY KEY, nombre TEXT);
        INSERT INTO admin_tiendas VALUES (3,'Tienda Centro');
        CREATE TABLE folios (id INTEGER PRIMARY KEY, id_usuario INTEGER, id_cliente_invitado INTEGER, orden TEXT,
            nombres TEXT, fecha TEXT, fecha_pago TEXT, total TEXT, estatus TEXT, shipping_carrier_id INTEGER, store_pickup INTEGER);
        INSERT INTO folios VALUES (42,NULL,7,'INV-7','Ana Guest','2026-11-16 10:00:00',NULL,'1.00','',21,3),
            (43,23,7,'REGISTERED','Otra cuenta','2026-11-16',NULL,'1.00','',21,NULL);
        CREATE TABLE productos (id INTEGER PRIMARY KEY, preciou TEXT);
        INSERT INTO productos VALUES (1,'120.00');
        CREATE TABLE registro_usuario (id INTEGER PRIMARY KEY, id_usuario INTEGER, id_cliente_invitado INTEGER,
            id_producto INTEGER, orden TEXT, codigo TEXT, nombre TEXT, precio TEXT, cantidad INTEGER);
        INSERT INTO registro_usuario VALUES (1,NULL,7,1,'INV-7','COD-1','Producto','100.0000',3),
            (2,NULL,8,1,'INV-7','FOREIGN','Privado','999.00',10),
            (3,23,7,1,'INV-7','REGISTERED','Privado','999.00',10);");
    $service = new \App\Services\OrderPdfService(new \App\Repositories\OrderPdfRepository($pdo));
    $order = $service->prepareGuest(42, 7);
    check($order['total_centavos'] === 30000 && count($order['productos']) === 1, 'Guest PDF uses only owned historical lines, not header total or foreign lines');
    check($order['transportista'] === 'Tienda Centro' && $order['correo'] === 'guest@example.com', 'Pickup branch and guest email come from stored data');
    check($order['ahorro_centavos'] === 6000, 'Shared PDF retains savings calculation');
    $_COOKIE['sn_guest_cart'] = str_repeat('a', 64);
    $links = new \App\Services\GuestOrderPdfLink();
    $token = $links->issue(42, 7);
    check($links->resolve($token, 7) === 42, 'Guest link resolves for its cookie and customer');
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $result = (new \App\Controllers\GuestOrderController(new TestGuestOrder(), $links))->create();
    check($links->resolve($result['data']['pdf_token'], 7) === 42, 'Successful processing response includes a protected PDF link');
    $controller = new \App\Controllers\GuestOrderPdfController($service);
    $_GET = array('cliente' => '7', 'token' => $token);
    ob_start(); $controller->show(); $bytes = ob_get_clean();
    check(substr($bytes, 0, 5) === '%PDF-' && strlen($bytes) > 1000, 'Guest controller generates the actual PDF');
    $_GET['cliente'] = '8';
    check($controller->show()['status'] === 404, 'Changing customer ID cannot expose another guest');
    $_GET['cliente'] = '7';
    $_COOKIE['sn_guest_cart'] = str_repeat('b', 64);
    check($controller->show()['status'] === 404, 'A different guest cookie cannot use this link');
    unset($_COOKIE['sn_guest_cart']);
    check($controller->show()['status'] === 401, 'Missing guest session cannot use the PDF');
    $_COOKIE['sn_guest_cart'] = str_repeat('a', 64);
    $_GET['token'] = (new \App\Services\OrderPdfLink(str_repeat('a',64)))->issue(42,7);
    check($controller->show()['status'] === 404, 'Registered-account link keys are separated from guest link keys');
    $_GET['token'] = $links->issue(43, 7);
    check($controller->show()['status'] === 404, 'Even a signed guest link cannot load a registered order');
    $_GET['token'] = $token;
    $_GET['token'][12] = $_GET['token'][12] === 'A' ? 'B' : 'A';
    check($controller->show()['status'] === 404, 'Tampering is rejected');
    echo "Guest PDF checks passed: saved receipt, pickup branch, shared layout and cookie/customer/account isolation.\n";
}
