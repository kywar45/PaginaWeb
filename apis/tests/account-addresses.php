<?php

require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/AuthService.php';
require __DIR__ . '/../src/Services/AddressService.php';
require __DIR__ . '/../src/Repositories/AddressRepository.php';

// Execute the production repository against an isolated in-memory database.
// Only MySQL's row-lock suffix and NOW() need translation for SQLite.
class AddressTestPdo extends PDO
{
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    #[\ReturnTypeWillChange]
    public function prepare($query, $options = array()) {
        return parent::prepare(str_replace(array(' FOR UPDATE', 'NOW()'), array('', 'CURRENT_TIMESTAMP'), $query), $options);
    }
}
class AddressTestAuth extends \App\Services\AuthService
{
    public $user = 23;
    public function __construct() {}
    public function currentUser() {
        if (!$this->user) throw new \App\Exceptions\ApiException('UNAUTHORIZED', 'Sesión requerida', 401);
        return array('usuario' => array('id' => $this->user));
    }
}
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function denied($callback, $status) {
    try { $callback(); } catch (\App\Exceptions\ApiException $error) {
        check($error->getStatus() === $status, 'Unexpected rejection status'); return;
    }
    throw new RuntimeException('Expected rejection');
}
$pdo = new AddressTestPdo();
$pdo->exec('CREATE TABLE usuario (id INTEGER PRIMARY KEY);
    INSERT INTO usuario VALUES (23), (99);
    CREATE TABLE direccion_envio (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, apellido TEXT, colonia TEXT,
        ciudad TEXT, estado TEXT, cp TEXT, direccion TEXT, telefono TEXT, id_usuario INTEGER, activo INTEGER,
        fecha_creacion TEXT, defecto INTEGER DEFAULT 0)');
$auth = new AddressTestAuth();
$service = new \App\Services\AddressService($auth, new \App\Repositories\AddressRepository($pdo));
$address = array('accion' => 'guardar', 'nombre' => 'Cliente', 'apellido' => 'Prueba', 'direccion' => 'Calle 1, interior 2',
    'colonia' => 'Centro', 'ciudad' => 'Ciudad de México', 'estado' => 'Ciudad de México', 'cp' => '01000', 'telefono' => '+52 55 1234 5678', 'defecto' => false);
check($service->listing()['direcciones'] === array(), 'Empty account');
$first = $service->change($address + array('id_usuario' => 99))['direcciones'][0];
check($first['defecto'] && $first['cp'] === '01000', 'First address becomes default and postal zero is retained');
check((int) $pdo->query('SELECT id_usuario FROM direccion_envio')->fetchColumn() === 23, 'Browser owner ignored');
$rows = $service->change(array_replace($address, array('direccion' => 'Calle 2')))['direcciones'];
$second = $rows[1];
check($rows[0]['id'] === $first['id'] && !$second['defecto'], 'Adding another preserves default');
$rows = $service->change(array('accion' => 'predeterminada', 'id' => $second['id']))['direcciones'];
check($rows[0]['id'] === $second['id'] && count(array_filter($rows, function ($row) { return $row['defecto']; })) === 1, 'Single new default');
$rows = $service->change(array_replace($address, array('id' => $second['id'], 'nombre' => 'Editado')))['direcciones'];
check($rows[0]['nombre'] === 'Editado' && $rows[0]['defecto'], 'Editing preserves default');
$auth->user = 99;
check($service->listing()['direcciones'] === array(), 'Other account cannot list addresses');
foreach (array('guardar', 'eliminar', 'predeterminada') as $action) {
    denied(function () use ($service, $address, $first, $action) {
        $service->change(array_replace($address, array('id' => $first['id'], 'accion' => $action)));
    }, 404);
    check(!$pdo->inTransaction(), 'Rejected operation rolls back');
}
$auth->user = 23;
$rows = $service->change(array('accion' => 'eliminar', 'id' => $second['id']))['direcciones'];
check(count($rows) === 1 && $rows[0]['defecto'], 'Removing default selects replacement');
check((int) $pdo->query('SELECT activo FROM direccion_envio WHERE id = ' . $second['id'])->fetchColumn() === 0, 'Soft deletion preserves row');
denied(function () use ($service, $second) { $service->change(array('accion' => 'predeterminada', 'id' => $second['id'])); }, 404);
foreach (array(array('cp' => '100'), array('nombre' => array()), array('id' => '../2'), array('defecto' => '1'), array('telefono' => 'abc'), array('estado' => '0'), array('estado' => 'Inventado')) as $invalid) {
    denied(function () use ($service, $address, $invalid) { $service->change(array_replace($address, $invalid)); }, 422);
}
check(count(\App\Services\AddressService::STATES) === 32, 'All 32 states provided');
foreach (\App\Services\AddressService::STATES as $state) {
    $rows = $service->change(array_replace($address, array('id' => $first['id'], 'estado' => $state)))['direcciones'];
    check($rows[0]['estado'] === $state, 'Selected state stored exactly, including accents');
}
$before = $pdo->query('SELECT COUNT(*) FROM direccion_envio')->fetchColumn();
$pdo->exec("CREATE TRIGGER reject_default BEFORE UPDATE ON direccion_envio WHEN NEW.nombre = 'Reject' BEGIN SELECT RAISE(ABORT, 'test failure'); END;");
try { $service->change(array_replace($address, array('nombre' => 'Reject', 'defecto' => true))); throw new RuntimeException('Expected database failure'); }
catch (PDOException $error) {}
check(!$pdo->inTransaction() && $pdo->query('SELECT COUNT(*) FROM direccion_envio')->fetchColumn() === $before, 'Partial save rolls back fully');
check($service->listing()['direcciones'][0]['defecto'], 'Failed save preserves previous default');
$auth->user = null;
denied(function () use ($service) { $service->listing(); }, 401);
denied(function () use ($service, $address) { $service->change($address); }, 401);
echo "Address tests passed: ownership, validation, CRUD, soft deletion, defaults and rollback.\n";
