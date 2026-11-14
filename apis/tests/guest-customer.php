<?php

namespace App\Http {
    class Request { public static $data; public static function json() { return self::$data; } }
    class Response {
        public static function success($data, $status) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code, 'message' => $message); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Services/AuthService.php';
    require __DIR__ . '/../src/Repositories/GuestCustomerRepository.php';
    require __DIR__ . '/../src/Services/GuestCustomerService.php';
    require __DIR__ . '/../src/Controllers/GuestCustomerController.php';

    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }
    function rejects($callback, $status) {
        try { $callback(); }
        catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === $status, 'Unexpected rejection'); return; }
        throw new \RuntimeException('Expected validation rejection');
    }
    class GuestCustomerTestAuth extends \App\Services\AuthService {
        public $user = null;
        public function __construct() {}
        public function optionalUser() { return $this->user; }
    }
    $pdo = new \PDO('sqlite::memory:');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $locks = 0; $releases = 0; $lockAvailable = true;
    $pdo->sqliteCreateFunction('GET_LOCK', function ($key, $timeout) use (&$locks, &$lockAvailable) { $locks++; return $lockAvailable ? 1 : 0; }, 2);
    $pdo->sqliteCreateFunction('RELEASE_LOCK', function ($key) use (&$releases) { $releases++; return 1; }, 1);
    $pdo->exec('CREATE TABLE web_clientes_invitados (
        id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, apellido TEXT NOT NULL, correo TEXT NOT NULL,
        estado TEXT NOT NULL, ciudad TEXT NOT NULL, colonia TEXT NOT NULL, direccion TEXT NOT NULL,
        codigo_postal TEXT NOT NULL, telefono TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $auth = new GuestCustomerTestAuth();
    $service = new \App\Services\GuestCustomerService(new \App\Repositories\GuestCustomerRepository($pdo), $auth);
    $input = array('nombre' => ' Ana ', 'apellido' => 'Pérez', 'correo' => 'ana@example.com',
        'estado' => 'Ciudad de México', 'ciudad' => 'Ciudad de México', 'colonia' => 'Centro',
        'direccion' => 'Artículo 123 #41', 'codigo_postal' => '06050', 'telefono' => '5534868537');
    $count = function () use ($pdo) { return (int) $pdo->query('SELECT COUNT(*) FROM web_clientes_invitados')->fetchColumn(); };
    $first = $service->register($input);
    $id = $first['cliente_invitado_id'];
    check(is_int($id) && $id > 0 && $count() === 1, 'Actual repository inserts the guest');
    $saved = $pdo->query('SELECT * FROM web_clientes_invitados')->fetch(\PDO::FETCH_ASSOC);
    check($saved['nombre'] === 'Ana' && $saved['codigo_postal'] === '06050' && $saved['created_at'], 'Whitespace trimmed, postal zero preserved, timestamps assigned by DB');
    check($service->register(array_merge($input, array('id' => 999, 'id_usuario' => 23))) === $first && $count() === 1, 'Exact retries reuse the ID and ignore browser identities');
    $other = $service->register(array_merge($input, array('direccion' => 'Otra dirección 123')));
    check($other['cliente_invitado_id'] !== $id && $count() === 2, 'Same email with changed address creates separate record without overwriting');
    foreach (array_keys($input) as $field) {
        rejects(function () use ($service, $input, $field) { $data = $input; unset($data[$field]); $service->register($data); }, 422);
        rejects(function () use ($service, $input, $field) { $data = $input; $data[$field] = array('invalid'); $service->register($data); }, 422);
    }
    foreach (array('nombre' => 100, 'apellido' => 100, 'correo' => 150, 'estado' => 100, 'ciudad' => 150, 'colonia' => 150, 'direccion' => 255, 'codigo_postal' => 10, 'telefono' => 20) as $field => $limit) {
        rejects(function () use ($service, $input, $field, $limit) { $data = $input; $data[$field] = str_repeat('á', $limit + 1); $service->register($data); }, 422);
    }
    foreach (array('correo' => 'not-an-email', 'estado' => 'Inventado', 'codigo_postal' => '6050', 'telefono' => '123', 'nombre' => "Ana\nPérez") as $field => $value) {
        rejects(function () use ($service, $input, $field, $value) { $data = $input; $data[$field] = $value; $service->register($data); }, 422);
    }
    check($count() === 2, 'Invalid fields never insert data');
    $auth->user = array('id' => 23);
    rejects(function () use ($service, $input) { $service->register($input); }, 409);
    $auth->user = null;
    $controller = new \App\Controllers\GuestCustomerController($service);
    \App\Http\Request::$data = $input;
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    check($controller->register()['status'] === 415 && $count() === 2, 'HTML form submissions rejected');
    $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
    check($controller->register()['data']['cliente_invitado_id'] === $id, 'JSON controller returns the persisted ID');
    $pdo->exec("CREATE TRIGGER fail_guest BEFORE INSERT ON web_clientes_invitados WHEN NEW.nombre = 'FALLAR' BEGIN SELECT RAISE(ABORT, 'simulated storage failure'); END");
    \App\Http\Request::$data = array_merge($input, array('nombre' => 'FALLAR'));
    check($controller->register()['status'] === 503, 'Storage failure does not report success');
    check(!$pdo->inTransaction() && $releases === $locks && $count() === 2, 'Storage failure rolls back and releases its lock');
    $lockAvailable = false;
    rejects(function () use ($service, $input) { $service->register($input); }, 409);
    check($count() === 2 && !$pdo->inTransaction(), 'Busy requests never insert');
    echo "Guest registration checks passed: real SQL insert, exact retries, schema limits, guest-only access, JSON and rollback.\n";
}
