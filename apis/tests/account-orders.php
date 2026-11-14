<?php
namespace App\Http {
    class Response {
        public static function success($data, $status = 200) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/UserRepository.php';
    require __DIR__ . '/../src/Services/AuthService.php';
    require __DIR__ . '/../src/Services/OrderPdfLink.php';
    require __DIR__ . '/../src/Controllers/AuthController.php';

    class OrdersPdo extends \PDO { public function __construct() {} }
    class OrderUsers extends \App\Repositories\UserRepository {
        public $countCalls = array();
        public $pageCalls = array();
        public $empty = false;
        public function __construct() {}
        public function findBySessionHash($hash) { return $hash === hash('sha256', str_repeat('a', 64)) ? array('id' => 23) : null; }
        public function countOrders($id) { $this->countCalls[] = $id; return $this->empty ? 0 : 3; }
        public function ordersPage($id, $limit, $offset) {
            $this->pageCalls[] = array($id, $limit, $offset);
            if ($this->empty) return array();
            return array_slice(array(
                array('id' => 3, 'orden' => 'SN-003', 'fecha' => '2026-11-13 12:00:00', 'fecha_pago' => null, 'total' => '$1,250.50', 'estatus' => 'Pendiente', 'nota' => 'PRIVATE'),
                array('id' => 2, 'orden' => 'SN-002', 'fecha' => '2026-11-12 12:00:00', 'fecha_pago' => '0000-00-00 00:00:00', 'total' => '', 'estatus' => 'Cancelado'),
                array('id' => 1, 'orden' => 'SN-001', 'fecha' => '0000-00-00 00:00:00', 'fecha_pago' => '2026-11-11 12:00:00', 'total' => '0', 'estatus' => 'Pagado'),
            ), $offset, $limit);
        }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    putenv('SESSION_COOKIE=sn_session');
    $users = new OrderUsers();
    $controller = new \App\Controllers\AuthController(new \App\Services\AuthService(new OrdersPdo(), $users));
    $_COOKIE = array();
    $_GET = array('id_usuario' => '999');
    check($controller->orders()['status'] === 401 && !$users->countCalls, 'Guests cannot list another user orders');
    $_COOKIE = array('sn_session' => str_repeat('a', 64));
    $_GET = array('page' => '1', 'limit' => '2', 'id_usuario' => '999');
    $first = $controller->orders()['data'];
    $links = new \App\Services\OrderPdfLink(str_repeat('a', 64));
    check($links->resolve($first['pedidos'][0]['pdf_token'], 23) === 3, 'List issues opaque links for the session owner');
    check($users->countCalls === array(23) && $users->pageCalls === array(array(23, 2, 0)), 'Owner comes from session, not GET');
    check($first['pagination']['total'] === 3 && $first['pagination']['total_pages'] === 2 && $first['pagination']['has_more'], 'All-order pagination');
    check(!$first['pedidos'][0]['pagado'] && !$first['pedidos'][1]['pagado'], 'Null and zero dates are unpaid, still visible');
    check($first['pedidos'][0]['total'] === 1250.5 && $first['pedidos'][1]['total'] === null, 'Formatted and missing totals');
    check(!isset($first['pedidos'][0]['nota']), 'Internal fields not exposed');
    $_GET['page'] = '2';
    $second = $controller->orders()['data'];
    check(count($second['pedidos']) === 1 && $second['pedidos'][0]['pagado'] && $second['pedidos'][0]['fecha'] === null, 'Paid order and legacy date normalization');
    check($second['pedidos'][0]['total'] === 0.0 && !$second['pagination']['has_more'], 'Zero total and last page');
    $users->empty = true;
    $_GET = array();
    check($controller->orders()['data']['pedidos'] === array(), 'Empty account');
    foreach (array(array('page' => '0'), array('page' => '-1'), array('page' => array('1')), array('limit' => '51'), array('limit' => '1.5')) as $invalid) {
        $_GET = $invalid;
        check($controller->orders()['status'] === 422, 'Invalid pagination rejected');
    }
    echo "Account order tests passed: authentication, owner, unpaid orders, pagination and normalization.\n";
}
