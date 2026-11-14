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
    require __DIR__ . '/../src/Controllers/AuthController.php';

    class SummaryPdo extends \PDO { public function __construct() {} }
    class SummaryUsers extends \App\Repositories\UserRepository {
        public $valid = true;
        public $calls = array();
        public $count = 3;
        public function __construct() {}
        public function findBySessionHash($hash) {
            return $this->valid && $hash === hash('sha256', 'test-session') ? array('id' => 23) : null;
        }
        public function countPaidOrders($userId) { $this->calls[] = $userId; return $this->count; }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    putenv('SESSION_COOKIE=sn_session');
    $users = new SummaryUsers();
    $controller = new \App\Controllers\AuthController(new \App\Services\AuthService(new SummaryPdo(), $users));
    $_COOKIE = array();
    $_GET = array('id_usuario' => '999');
    check($controller->summary()['status'] === 401 && !$users->calls, 'Guests cannot query a count using a supplied ID');
    $_COOKIE = array('sn_session' => 'test-session');
    $result = $controller->summary();
    check($result['status'] === 200 && $result['data'] === array('pedidos_pagados' => 3), 'Summary returns only a count');
    check($users->calls === array(23), 'Uses authenticated user, ignores browser ID');
    $users->count = 0;
    check($controller->summary()['data']['pedidos_pagados'] === 0, 'No paid orders is a valid zero');
    $users->valid = false;
    $before = count($users->calls);
    check($controller->summary()['status'] === 401 && count($users->calls) === $before, 'Invalid session cannot query orders');
    echo "Account summary authorization tests passed.\n";
}
