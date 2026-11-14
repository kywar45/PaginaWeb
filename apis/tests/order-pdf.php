<?php
namespace App\Http {
    class Response {
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/UserRepository.php';
    require __DIR__ . '/../src/Repositories/OrderPdfRepository.php';
    require __DIR__ . '/../src/Services/AuthService.php';
    require __DIR__ . '/../src/Services/OrderPdfLink.php';
    require __DIR__ . '/../src/Services/OrderPdfService.php';
    require __DIR__ . '/../src/Pdf/OrderPdf.php';
    require __DIR__ . '/../src/Controllers/OrderPdfController.php';

    class PdfOrders extends \App\Repositories\OrderPdfRepository {
        public $itemCalls = 0;
        public $header;
        public $rows;
        public function __construct() {
            $this->header = array('id' => 42, 'id_usuario' => 23, 'orden' => 'SN-42', 'nombres' => 'Mar&iacute;a Prueba',
                'fecha' => '2026-11-13 10:00:00', 'fecha_pago' => null, 'total' => '650.00',
                'estatus' => 'Pendiente', 'carrier_name' => 'Paquetería de prueba', 'carrier_envio' => '1',
                'envio' => 'No', 'paqueteria' => 'LEGACY IGNORED');
            $base = array('id' => 1, 'id_usuario' => 23, 'id_producto' => 8, 'codigo' => 'GEL-01', 'nombre' => 'Gel &amp; brillo', 'orden' => 'SN-42');
            $this->rows = array(array_merge($base, array('cantidad' => '2', 'precio' => '100.00')),
                array_merge($base, array('cantidad' => '3', 'precio' => '150.00')));
        }
        public function findOwned($id, $userId) { return $id === 42 && $userId === 23 ? $this->header : null; }
        public function itemsForOrder($reference, $userId) {
            if ($userId !== 23 || $reference !== 'SN-42') throw new \RuntimeException('Owner must scope historical lines');
            $this->itemCalls++; return $this->rows;
        }
    }
    class PdfAuth extends \App\Services\AuthService {
        public $id = 23;
        public function __construct() {}
        public function currentUser() {
            if ($this->id === null) throw new \App\Exceptions\ApiException('UNAUTHENTICATED', 'Sesion no valida.', 401);
            return array('usuario' => array('id' => $this->id));
        }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    $repo = new PdfOrders();
    $service = new \App\Services\OrderPdfService($repo);
    $data = $service->prepare(42, 23, 'cliente@example.com');
    check($data['subtotal_centavos'] === 65000 && $data['total_centavos'] === 65000 && $data['piezas'] === 5, 'Historical line totals in integer cents');
    check(count($data['productos']) === 2 && $data['productos'][0]['precio_centavos'] !== $data['productos'][1]['precio_centavos'], 'Same-code variants keep their own historical prices');
    check($data['cliente'] === 'María Prueba' && $data['productos'][0]['nombre'] === 'Gel & brillo', 'HTML entities decoded');
    check(!isset($data['direccion']) && !isset($data['telefono']) && $data['correo'] === 'cliente@example.com' && !$data['pagado'], 'No legacy address fields; only trusted session email');
    check($data['entrega_tipo'] === 'Envío por paquetería' && $data['transportista'] === 'Paquetería de prueba', 'New carrier relation overrides legacy fields');
    $repo->header['carrier_envio'] = '0';
    check($service->prepare(42, 23)['entrega_tipo'] === 'Entrega en punto', 'Delivery point');
    $repo->header['carrier_envio'] = null;
    check($service->prepare(42, 23)['entrega_tipo'] === 'Método por confirmar', 'Missing carrier not guessed');
    $repo->header['fecha_pago'] = '0000-00-00 00:00:00';
    check(!$service->prepare(42, 23)['pagado'], 'Legacy zero date is not paid');
    $repo->header['fecha_pago'] = '2026-11-13 10:30:00';
    $repo->header['total'] = '700.00';
    check($service->prepare(42, 23)['ajustes_centavos'] === 5000 && $service->prepare(42, 23)['pagado'], 'Saved total reconciles with historical items');
    $before = $repo->itemCalls;
    foreach (array(array(42, 99), array(99, 23)) as $ids) {
        try { $service->prepare($ids[0], $ids[1]); throw new \RuntimeException('Expected missing order'); }
        catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === 404 && $repo->itemCalls === $before, 'Foreign and missing folios do not load items'); }
    }
    $originalRows = $repo->rows;
    $repo->rows = array_fill(0, 3, array('codigo' => 'GEL-01', 'nombre' => 'Precio decimal', 'cantidad' => '1', 'precio' => '0.3333'));
    $decimal = $service->prepare(42, 23);
    check($decimal['subtotal_centavos'] === 100 && $decimal['redondeo_centavos'] === 1 && $decimal['productos'][0]['precio_unidades'] === 3333, 'All four historical decimals retained before final rounding');
    $repo->rows = $originalRows;
    $auth = new PdfAuth();
    putenv('SESSION_COOKIE=sn_session');
    $_COOKIE = array('sn_session' => str_repeat('a', 64));
    $links = new \App\Services\OrderPdfLink($_COOKIE['sn_session']);
    $controller = new \App\Controllers\OrderPdfController($auth, $service);
    $_GET = array('token' => $links->issue(42, 23), 'id' => '99', 'id_usuario' => '99');
    ob_start(); $controller->show(); $bytes = ob_get_clean();
    check(substr($bytes, 0, 5) === '%PDF-' && strlen($bytes) > 1000, 'Inline PDF generated for session owner; browser ID ignored');
    $auth->id = 99;
    check($controller->show()['status'] === 404, 'Controller ownership check');
    $_GET['token'] = $links->issue(42, 99);
    check($controller->show()['status'] === 404, 'Even valid token cannot bypass database ownership');
    $auth->id = null;
    check($controller->show()['status'] === 401, 'PDF is not public');
    $auth->id = 23;
    foreach (array(null, '0', '-1', '42 OR 1=1', array('42')) as $invalid) {
        $_GET = array('id' => $invalid);
        check($controller->show()['status'] === 404, 'Direct numeric PDF API is disabled');
    }
    $_GET = array('token' => $links->issue(42, 23));
    $_GET['token'][12] = $_GET['token'][12] === 'A' ? 'B' : 'A';
    $before = $repo->itemCalls;
    check($controller->show()['status'] === 404 && $repo->itemCalls === $before, 'Modified token never loads order details');
    echo "Order PDF tests passed: ownership, historical amounts, variants, delivery modes and binary response.\n";
}
