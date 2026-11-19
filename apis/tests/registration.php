<?php
namespace App\Services {
    // Capture cookies without sending headers or creating a real browser session.
    function setcookie($name, $value, $options) { $_COOKIE[$name] = $value; return true; }
}
namespace App\Http {
    class Request { public static $data = array(); public static function json() { return self::$data; } }
    class Response {
        public static function success($data, $status) { return array('data' => $data, 'status' => $status); }
        public static function error($code, $message, $status) { return array('code' => $code, 'message' => $message, 'status' => $status); }
    }
}
namespace {
    foreach (array('Exceptions/ApiException', 'Repositories/RegistrationRepository', 'Repositories/UserRepository',
        'Services/CartIdentity', 'Services/AuthService', 'Services/GuestOrderEmailService', 'Services/RegistrationData',
        'Services/RegistrationLimiter', 'Services/RegistrationEmailService', 'Services/RegistrationService', 'Controllers/RegistrationController') as $file) require __DIR__ . '/../src/' . $file . '.php';
    class RegisterPdo extends \PDO {
        public function __construct() {
            parent::__construct('sqlite::memory:');
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->sqliteCreateFunction('GET_LOCK', function () { return 1; });
            $this->sqliteCreateFunction('RELEASE_LOCK', function () { return 1; });
            $this->exec("CREATE TABLE usuario (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, apellido TEXT NOT NULL,
                direccion TEXT NOT NULL, colonia TEXT NOT NULL, ciudad TEXT NOT NULL, estado TEXT NOT NULL, codigop TEXT NOT NULL,
                telefono TEXT NOT NULL, correo TEXT NOT NULL COLLATE NOCASE UNIQUE, rfc TEXT NOT NULL, pass TEXT NOT NULL,
                orden TEXT DEFAULT '0', token_sesion TEXT NOT NULL, fecha_registro TEXT NOT NULL, token_registro TEXT NOT NULL,
                user_vip INTEGER NOT NULL DEFAULT 0);
                CREATE TABLE web_registro_confirmaciones (usuario_id INTEGER PRIMARY KEY, token_hash TEXT UNIQUE NOT NULL,
                    expires_at TEXT NOT NULL, requested_at TEXT NOT NULL, confirmed_at TEXT, mail_accepted_at TEXT,
                    mail_attempts INTEGER NOT NULL DEFAULT 0);");
        }
        #[\ReturnTypeWillChange]
        public function prepare($sql, $options = array()) { return parent::prepare(str_replace(array(' FOR UPDATE', ' LIMIT 1', 'BINARY pass'), array('', '', 'pass COLLATE BINARY'), $sql), $options); }
    }
    class NoRegisterLimit extends \App\Services\RegistrationLimiter { public function consume($email) {} }
    class RegisterMail extends \App\Services\GuestOrderEmailService {
        public $messages = array(); public $accept = true;
        public function __construct() {}
        public function sendTransactional($to, $subject, $html) { $this->messages[] = array($to, $subject, $html); return $this->accept; }
    }
    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    function rejects($callback, $status) {
        try { $callback(); } catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === $status, 'Unexpected status: ' . $error->getMessage()); return $error->getErrorCodeName(); }
        throw new \RuntimeException('Expected API rejection');
    }
    function emailToken($transport) { preg_match('/confirmar-correo\?token=([a-f0-9]{64})/', end($transport->messages)[2], $m); return $m[1]; }
    $pdo = new RegisterPdo();
    $repository = new \App\Repositories\RegistrationRepository($pdo);
    $transport = new RegisterMail();
    $service = new \App\Services\RegistrationService($repository, new \App\Services\RegistrationEmailService($transport), new NoRegisterLimit());
    $auth = new \App\Services\AuthService($pdo, new \App\Repositories\UserRepository($pdo));
    $input = array('nombre' => ' Ana ', 'apellido' => 'Pérez', 'direccion' => 'Calle 1, interior 2', 'colonia' => 'Centro', 'ciudad' => 'Ciudad de México',
        'estado' => 'Ciudad de México', 'codigop' => '06050', 'telefono' => '+52 55 1234 5678', 'correo' => ' ANA@EXAMPLE.COM ', 'rfc' => '',
        'password' => 'correct-password', 'password_confirmation' => 'correct-password', 'user_vip' => 1, 'tipo' => 'admin', 'token_registro' => 'forged');
    $_COOKIE = array();
    $result = $service->register($input);
    $user = $repository->findEmail('ana@example.com');
    check($result['verification_required'] && $result['email_status'] === 'accepted', 'Registration requests verification without logging in');
    check($user['token_registro'] === '' && $_COOKIE === array(), 'Pending account has no confirmation or session');
    check($user['pass'] !== $input['password'] && password_verify($input['password'], $user['pass']), 'Only a password hash is stored');
    check((int) $pdo->query('SELECT user_vip FROM usuario')->fetchColumn() === 0, 'Browser privileges ignored; obsolete columns not required');
    check($pdo->query('SELECT codigop FROM usuario')->fetchColumn() === '06050', 'Postal zero preserved');
    $token = emailToken($transport);
    $pendingSnapshot = $pdo->query('SELECT * FROM web_registro_confirmaciones')->fetchAll(\PDO::FETCH_ASSOC);
    check($service->validate(array('token' => $token))['valid'], 'Pending link can be checked before showing confirmation');
    check($pdo->query('SELECT * FROM web_registro_confirmaciones')->fetchAll(\PDO::FETCH_ASSOC) === $pendingSnapshot && $user['token_registro'] === '', 'Checking a link is read-only and does not confirm or send mail');
    check($pdo->query('SELECT token_hash FROM web_registro_confirmaciones')->fetchColumn() === hash('sha256', $token), 'Only hashed confirmation token is stored');
    check(!isset($result['token']) && strpos($transport->messages[0][2], '24 horas') !== false, 'Private link sent only by email with expiry notice');
    check(rejects(function () use ($service, $input) { $service->register(array_replace($input, array('correo' => 'ana@example.com'))); }, 409) === 'EMAIL_EXISTS', 'Case and whitespace variants cannot create a second account');
    try { $pdo->exec("INSERT INTO usuario SELECT NULL,nombre,apellido,direccion,colonia,ciudad,estado,codigop,telefono,'ANA@EXAMPLE.COM',rfc,pass,orden,token_sesion,fecha_registro,token_registro,user_vip FROM usuario"); throw new \RuntimeException('Expected unique violation'); }
    catch (\PDOException $error) {}
    check(rejects(function () use ($auth) { $auth->login('ana@example.com', 'correct-password', false); }, 403) === 'EMAIL_UNVERIFIED', 'Correct password cannot bypass unconfirmed email');
    rejects(function () use ($auth) { $auth->login('ana@example.com', 'wrong-password', false); }, 401);
    $pdo->exec("UPDATE usuario SET token_sesion = '" . hash('sha256', 'old-session') . "'");
    $_COOKIE['sn_session'] = 'old-session';
    check($auth->optionalUser() === null, 'An old session cannot bypass pending verification');
    rejects(function () use ($service) { $service->confirm(array('token' => str_repeat('b', 64))); }, 404);
    $pdo->exec("UPDATE web_registro_confirmaciones SET expires_at = '2000-01-01 00:00:00'");
    rejects(function () use ($service, $token) { $service->validate(array('token' => $token)); }, 410);
    rejects(function () use ($service, $token) { $service->confirm(array('token' => $token)); }, 410);
    check($repository->findEmail('ana@example.com')['token_registro'] === '', 'Expired token cannot activate account');
    rejects(function () use ($service) { $service->resend(array('correo' => 'ana@example.com', 'password' => 'wrong')); }, 401);
    $service->resend(array('correo' => 'ana@example.com', 'password' => 'correct-password'));
    $newToken = emailToken($transport);
    check($newToken !== $token, 'Resend rotates token');
    rejects(function () use ($service, $token) { $service->confirm(array('token' => $token)); }, 404);
    check($service->confirm(array('token' => $newToken))['verified'], 'Valid current link confirms the email');
    check($repository->findEmail('ana@example.com')['token_registro'] === hash('sha256', $newToken), 'Confirmation fills token_registro');
    check(rejects(function () use ($service, $newToken) { $service->validate(array('token' => $newToken)); }, 410) === 'CONFIRMATION_USED', 'Used confirmation link is rejected before showing the action');
    check(rejects(function () use ($service, $newToken) { $service->confirm(array('token' => $newToken)); }, 410) === 'CONFIRMATION_USED', 'Actual confirmation remains single-use under concurrent tabs');
    $login = $auth->login('ana@example.com', 'correct-password', false);
    check($login['usuario']['id'] === (int) $user['id'] && !isset($login['usuario']['pass']) && !isset($login['usuario']['token_registro']), 'Verified login succeeds without exposing credentials');
    check($auth->currentUser()['usuario']['id'] === (int) $user['id'], 'Verified session is usable');
    $before = count($transport->messages);
    check($service->resend(array('correo' => 'ana@example.com', 'password' => 'correct-password'))['already_verified'] && count($transport->messages) === $before, 'Verified account does not receive redundant activation messages');
    $pdo->exec("UPDATE usuario SET pass = 'legacy-password', token_registro = 'existing-confirmed-token'");
    check($auth->login('ana@example.com', 'legacy-password', false)['usuario']['id'] === (int) $user['id'], 'Existing confirmed accounts preserve legacy password compatibility');
    putenv('AUTH_ALLOW_LEGACY_PASSWORDS=0');
    rejects(function () use ($auth) { $auth->login('ana@example.com', 'legacy-password', false); }, 401);
    putenv('AUTH_ALLOW_LEGACY_PASSWORDS');
    foreach (array(array('estado' => 'Inventado'), array('codigop' => '6050'), array('password_confirmation' => 'bad'), array('direccion' => 'Calle 😎'), array('password' => str_repeat('a',73)), array('rfc' => 'bad')) as $bad) {
        rejects(function () use ($service, $input, $bad) { $service->register(array_replace($input, $bad)); }, 422);
    }
    $transport->accept = false;
    check($service->register(array_replace($input, array('correo' => 'pending@example.com')))['email_status'] === 'pending', 'Mail failure preserves a pending account for recovery');
    check($repository->findEmail('pending@example.com')['token_registro'] === '', 'Failed mail never confirms account');
    $pdo->exec('DROP TABLE web_registro_confirmaciones');
    try { $service->register(array_replace($input, array('correo' => 'rollback@example.com'))); throw new \RuntimeException('Expected storage failure'); } catch (\PDOException $error) {}
    check(!$repository->findEmail('rollback@example.com') && !$pdo->inTransaction(), 'Missing confirmation table rolls back account creation');
    $controller = new \App\Controllers\RegistrationController($service);
    $_SERVER['CONTENT_TYPE'] = 'text/plain';
    check($controller->register()['status'] === 415, 'Cross-site simple form content cannot submit registrations');
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    \App\Http\Request::$data = 'invalid-object';
    check($controller->register()['status'] === 422, 'Invalid JSON shape rejected before service writes');
    \App\Http\Request::$data = array_replace($input, array('correo' => 'controller-error@example.com'));
    $failure = $controller->register();
    check($failure['status'] === 503 && strpos($failure['message'], 'SQL') === false && !$repository->findEmail('controller-error@example.com'), 'Storage error does not leak SQL or leave a half-created account');
    echo "Registration tests passed: real SQL, unique email, protected passwords, pending login/session block, token expiry, resend, confirmation, recovery and rollback.\n";
}
