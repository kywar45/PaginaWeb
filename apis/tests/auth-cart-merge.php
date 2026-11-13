<?php
namespace App\Repositories {
    class CartRepository { public function __construct(\PDO $pdo) {} }
    class ProductRepository { public function __construct(\PDO $pdo) {} }
}
namespace App\Services {
    // Aislar el contrato login/fusion; cart.php comprueba el calculo y la transaccion.
    class CartService {
        public static $calls = array();
        public static $fail = false;
        public function __construct($carts, $products, $owner, $user, $guest) {
            self::$calls[] = array($owner, $user, $guest);
        }
        public function execute($action) {
            if ($action !== 'fusionar') throw new \RuntimeException('Expected merge on login');
            if (self::$fail) throw new \RuntimeException('Database unavailable');
            return array('fusion' => array('fusionado' => true, 'piezas' => 3, 'no_disponibles' => 1));
        }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/UserRepository.php';
    require __DIR__ . '/../src/Services/CartIdentity.php';
    require __DIR__ . '/../src/Services/AuthService.php';

    class LoginPdo extends \PDO {
        public function __construct() {}
        public function beginTransaction(): bool { return true; }
        public function commit(): bool { return true; }
        public function inTransaction(): bool { return false; }
    }
    class LoginUsers extends \App\Repositories\UserRepository {
        public $user;
        public function __construct() {}
        public function findByCorreo($correo) { return $this->user; }
        public function updateSessionHash($id, $hash) {}
    }
    function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
    $users = new LoginUsers();
    $users->user = array('id' => 23, 'nombre' => 'Cliente', 'apellido' => 'Prueba',
        'correo' => 'test@example.com', 'tipo' => 'Cliente', 'nivel' => 1, 'user_vip' => 1,
        'pass' => password_hash('correct-password', PASSWORD_DEFAULT));
    $auth = new \App\Services\AuthService(new LoginPdo(), $users);
    $_COOKIE = array('sn_guest_cart' => str_repeat('a', 64));
    $result = $auth->login('test@example.com', 'correct-password', false);
    check($result['carrito_fusion']['fusionado'] && $result['carrito_fusion']['piezas'] === 3, 'Login returns merge notice');
    $call = \App\Services\CartService::$calls[0];
    check($call[0] === array('user_id' => 23, 'guest_hash' => null), 'Merge target is authenticated user from DB');
    check($call[1]['user_vip'] === 1 && !isset($call[1]['pass']), 'Pricing receives trusted public user, never credentials');
    check($call[2]['user_id'] === 0 && $call[2]['guest_hash'] === hash('sha256', str_repeat('a', 64)), 'Merge source is current visitor cookie');

    \App\Services\CartService::$fail = true;
    $result = $auth->login('test@example.com', 'correct-password', false);
    check($result['usuario']['id'] === 23 && $result['carrito_fusion']['pendiente'], 'Cart failure does not turn a successful login into an auth error');
    check(strpos($result['carrito_fusion']['mensaje'], 'Database') === false, 'Internal database errors are not exposed');

    unset($_COOKIE['sn_guest_cart']);
    $before = count(\App\Services\CartService::$calls);
    $result = $auth->login('test@example.com', 'correct-password', true);
    check(!$result['carrito_fusion']['fusionado'] && count(\App\Services\CartService::$calls) === $before, 'Login without guest cookie does not invent or merge another visitor');
    try {
        $auth->login('test@example.com', 'wrong-password', false);
        throw new \RuntimeException('Expected invalid credentials');
    } catch (\App\Exceptions\ApiException $error) {
        check($error->getStatus() === 401 && count(\App\Services\CartService::$calls) === $before, 'Failed login never merges carts');
    }
    echo "Login/cart merge contract tests passed.\n";
}
