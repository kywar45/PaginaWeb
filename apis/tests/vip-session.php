<?php

require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Repositories/UserRepository.php';
require __DIR__ . '/../src/Services/AuthService.php';

class SessionTestPdo extends \PDO { public function __construct() {} }
class SessionTestUsers extends \App\Repositories\UserRepository
{
    public $user;
    public $hash;
    public function __construct() {}
    public function findBySessionHash($hash) { $this->hash = $hash; return $this->user; }
}
function check($condition, $message) {
    if (!$condition) throw new \RuntimeException($message);
}
putenv('SESSION_COOKIE=sn_test_session');
$users = new SessionTestUsers();
$auth = new \App\Services\AuthService(new SessionTestPdo(), $users);
$_COOKIE = array();
check($auth->optionalUser() === null, 'Anonymous product requests do not require login');
$_COOKIE['sn_test_session'] = 'test-session-token';
$users->user = array('id' => 1, 'nombre' => 'Cliente', 'apellido' => 'VIP', 'correo' => 'test@example.com', 'tipo' => 'Cliente', 'nivel' => '1', 'user_vip' => '1');
$user = $auth->optionalUser();
check($user['user_vip'] === 1, 'VIP flag resolved from server session');
check($users->hash === hash('sha256', 'test-session-token'), 'Lookup uses hashed session token');
$users->user['user_vip'] = 0;
check($auth->optionalUser()['user_vip'] === 0, 'Normal user remains normal');
$users->user = null;
check($auth->optionalUser() === null, 'Expired session falls back to guest');
echo "VIP session tests passed.\n";
