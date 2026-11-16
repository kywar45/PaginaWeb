<?php

require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Services/AuthService.php';
require __DIR__ . '/../src/Repositories/GuestCustomerRepository.php';
require __DIR__ . '/../src/Services/GuestCustomerService.php';
require __DIR__ . '/../src/Repositories/CartRepository.php';
require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';
require __DIR__ . '/../src/Services/CartPricing.php';
require __DIR__ . '/../src/Services/CartService.php';
require __DIR__ . '/../src/Repositories/GuestOrderRepository.php';
require __DIR__ . '/../src/Services/GuestOrderService.php';

function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function rejects($callback, $status) {
    try { $callback(); }
    catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === $status, 'Unexpected rejection status'); return; }
    throw new RuntimeException('Expected rejection');
}
class GuestOrderPdo extends PDO {
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    #[\ReturnTypeWillChange]
    public function prepare($query, $options = array()) { return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options); }
}
class GuestOrderAuth extends \App\Services\AuthService {
    public $user = null;
    public function __construct() {}
    public function optionalUser() { return $this->user; }
}
class GuestOrderProducts extends \App\Repositories\ProductRepository {
    public $rows = array();
    public function __construct() {}
    public function cartProducts($ids) { return array_values(array_intersect_key($this->rows, array_flip($ids))); }
}
function product($id, $price) {
    return array('id' => $id, 'codigo' => 'COD-' . $id, 'nombre' => 'Producto ' . $id, 'descripcion' => '',
        'preciou' => $price, 'preciom' => 0, 'precioc' => 0, 'topem' => 0, 'topec' => 0,
        'descuento' => 'no', 'descuento_general' => 0, 'active' => 1, 'estatus' => 1,
        'imagen' => null, 'visitas' => 0, 'minimo' => 1, 'sub_categoria' => null,
        'promo' => 0, 'barcode' => null, 'product_nuevo' => 0);
}
$pdo = new GuestOrderPdo();
$locks = array();
$pdo->sqliteCreateFunction('GET_LOCK', function ($key, $timeout) use (&$locks) { $locks[$key] = true; return 1; }, 2);
$pdo->sqliteCreateFunction('RELEASE_LOCK', function ($key) use (&$locks) { unset($locks[$key]); return 1; }, 1);
$pdo->sqliteCreateFunction('NOW', function () { return date('Y-m-d H:i:s'); }, 0);
$pdo->exec('PRAGMA foreign_keys = ON;
CREATE TABLE web_clientes_invitados (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, apellido TEXT NOT NULL,
    correo TEXT NOT NULL, estado TEXT NOT NULL, ciudad TEXT NOT NULL, colonia TEXT NOT NULL, direccion TEXT NOT NULL,
    codigo_postal TEXT NOT NULL, telefono TEXT NOT NULL);
CREATE TABLE shipping_carriers (id INTEGER PRIMARY KEY, envio INTEGER, estatus INTEGER);
INSERT INTO shipping_carriers VALUES (21,0,1), (8,1,1), (9,1,0), (22,0,1);
CREATE TABLE imp_order_statuses (id INTEGER PRIMARY KEY);
INSERT INTO imp_order_statuses VALUES (1);
CREATE TABLE admin_tiendas (id INTEGER PRIMARY KEY, estatus INTEGER, store INTEGER);
INSERT INTO admin_tiendas VALUES (25,1,1), (26,0,1), (27,1,1), (28,1,0);
CREATE TABLE carrito (id INTEGER PRIMARY KEY, id_usuario INTEGER, guest_hash TEXT, id_producto INTEGER,
    nombre TEXT, codigo TEXT, precio NUMERIC, cantidad INTEGER, activo INTEGER);
CREATE TABLE registro_usuario (id INTEGER PRIMARY KEY AUTOINCREMENT, id_usuario INTEGER NULL,
    id_cliente_invitado INTEGER REFERENCES web_clientes_invitados(id), id_producto INTEGER NOT NULL,
    nombre TEXT NOT NULL, codigo TEXT NOT NULL, precio NUMERIC NOT NULL, cantidad INTEGER NOT NULL,
    fecha TEXT NOT NULL, orden TEXT NOT NULL, color TEXT NOT NULL, envio TEXT NOT NULL,
    paqueteria TEXT NOT NULL, rfc TEXT NOT NULL, nombred TEXT NOT NULL, apellidod TEXT NOT NULL,
    direcciond TEXT NOT NULL, coloniad TEXT NOT NULL, ciudadd TEXT NOT NULL, estadod TEXT NOT NULL,
    codigopd TEXT NOT NULL, telefonod TEXT NOT NULL, estatus TEXT NOT NULL, almacen TEXT NOT NULL,
    fecha_procesado TEXT DEFAULT NULL);
CREATE TABLE folios (id INTEGER PRIMARY KEY AUTOINCREMENT, id_usuario INTEGER NULL,
    id_cliente_invitado INTEGER REFERENCES web_clientes_invitados(id), nombres TEXT NOT NULL, orden TEXT NOT NULL,
    shipping_carrier_id INTEGER REFERENCES shipping_carriers(id), store_pickup INTEGER NULL REFERENCES admin_tiendas(id), cantidad INTEGER NOT NULL, total NUMERIC NOT NULL,
    order_status_id INTEGER REFERENCES imp_order_statuses(id), fecha TEXT, estatus_ventas TEXT NOT NULL,
    envio TEXT NOT NULL, paqueteria TEXT NOT NULL, estatus TEXT NOT NULL, nota TEXT NOT NULL, efectivo TEXT NOT NULL,
    monto TEXT NOT NULL, cajas TEXT NOT NULL, id_operador TEXT NOT NULL, recojo TEXT NOT NULL, medidas TEXT NOT NULL,
    vendedora TEXT NOT NULL, pendiente TEXT NOT NULL, venta_paqueteria TEXT NOT NULL, iva TEXT NOT NULL,
    saldo_pendiente TEXT NOT NULL, marca TEXT NOT NULL, empaquetado TEXT NOT NULL, verificado TEXT NOT NULL,
    guia_gratis TEXT NOT NULL, marcado TEXT NOT NULL DEFAULT \'0\', seguro TEXT NOT NULL, id_detalle_orden INTEGER NOT NULL)');
$owner = array('user_id' => 0, 'guest_hash' => str_repeat('a', 64));
$insertCart = $pdo->prepare('INSERT INTO carrito VALUES (?,0,?,?,?, ?,?, ?,1)');
$insertCart->execute(array(1, $owner['guest_hash'], 1, 'Anterior', 'COD-1', '0.01', 2));
$insertCart->execute(array(2, $owner['guest_hash'], 2, 'Anterior', 'COD-2', '0.01', 3));
$insertCart->execute(array(3, str_repeat('b', 64), 1, 'Privado', 'COD-1', '999.00', 100));
$products = new GuestOrderProducts();
$products->rows = array(1 => product(1, 100), 2 => product(2, 50));
$auth = new GuestOrderAuth();
$customers = new \App\Services\GuestCustomerService(new \App\Repositories\GuestCustomerRepository($pdo), $auth);
$cart = new \App\Services\CartService(new \App\Repositories\CartRepository($pdo), $products, $owner);
$service = new \App\Services\GuestOrderService($customers, new \App\Repositories\GuestOrderRepository($pdo), $cart);
$data = array('nombre' => 'Ana', 'apellido' => 'Pérez', 'correo' => 'ana@example.com', 'estado' => 'Ciudad de México',
    'ciudad' => 'Ciudad de México', 'colonia' => 'Centro', 'direccion' => 'Artículo 123 #41', 'codigo_postal' => '06050',
    'telefono' => '5534868537', 'tipo_entrega' => 0, 'shipping_carrier_id' => 8, 'sucursal_id' => 25,
    'id_cliente_invitado' => 999, 'id_usuario' => 23, 'cantidad' => 1, 'total' => '0.01');
$result = $service->create($data);
$folio = $pdo->query('SELECT * FROM folios')->fetch(PDO::FETCH_ASSOC);
check($folio['id_usuario'] === null && (int) $folio['id_cliente_invitado'] === 1 && $folio['nombres'] === 'Ana Pérez', 'Guest identity and names come from validated persisted customer');
check($folio['orden'] === 'INV-1' && (int) $folio['shipping_carrier_id'] === 21, 'Personal delivery uses carrier 21, ignoring forged carrier ID');
check((int) $folio['store_pickup'] === 25, 'Personal delivery stores the selected branch ID');
check((int) $folio['cantidad'] === 5 && (float) $folio['total'] === 350.0, 'Quantity and fresh prices come only from the guest cart');
check((int) $folio['order_status_id'] === 1 && $folio['estatus_ventas'] === 'no contactado' && $folio['fecha'], 'Initial status and server timestamp');
check($service->create($data)['folio_id'] === $result['folio_id'] && (int) $pdo->query('SELECT COUNT(*) FROM folios')->fetchColumn() === 1, 'Retry does not duplicate the reference');
check((int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Neither own nor foreign carts are emptied');
$lines = $pdo->query('SELECT * FROM registro_usuario ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
check(count($lines) === 2 && $result['productos_registrados'] === 2, 'Each product is saved once, including after retries');
check($lines[0]['id_usuario'] === null && (int) $lines[0]['id_cliente_invitado'] === 1 && $lines[0]['orden'] === 'INV-1', 'Line identity belongs to the guest and folio');
check((int) $lines[0]['id_producto'] === 1 && $lines[0]['nombre'] === 'Producto 1' && $lines[0]['codigo'] === 'COD-1', 'Product identity comes from server data, not stale browser fields');
check((float) $lines[0]['precio'] === 100.0 && (int) $lines[0]['cantidad'] === 2 && $lines[0]['fecha'], 'Line price, quantity and timestamp persisted');
check((float) $lines[1]['precio'] === 50.0 && (int) $lines[1]['cantidad'] === 3, 'Each variant retains its own unit price and quantity');
check($lines[0]['direcciond'] === '' && $lines[0]['estatus'] === '' && $lines[0]['fecha_procesado'] === null, 'Unrequested legacy fields remain empty');
check((float) $pdo->query('SELECT SUM(precio * cantidad) FROM registro_usuario')->fetchColumn() === (float) $folio['total'], 'Historical line totals reconcile with folio');
check((float) $pdo->query('SELECT precio FROM carrito WHERE id = 3')->fetchColumn() === 999.0, 'Foreign guest cart remains untouched');
$national = array_merge($data, array('nombre' => 'Luis', 'tipo_entrega' => 1, 'shipping_carrier_id' => 8, 'sucursal_id' => null));
$nationalResult = $service->create($national);
check($nationalResult['orden'] === 'INV-2' && $nationalResult['total'] === 350, 'National delivery uses selected active carrier');
check($pdo->query("SELECT store_pickup FROM folios WHERE orden = 'INV-2'")->fetchColumn() === null, 'National shipping stores NULL for pickup');
rejects(function () use ($service, $national) { $service->create(array_merge($national, array('shipping_carrier_id' => 21))); }, 409);
rejects(function () use ($service, $national) { $service->create(array_merge($national, array('shipping_carrier_id' => 9))); }, 409);
rejects(function () use ($service, $data) { $service->create(array_merge($data, array('sucursal_id' => 26))); }, 409);
rejects(function () use ($service, $data) { $service->create(array_merge($data, array('sucursal_id' => 28))); }, 409);
rejects(function () use ($service, $data) { $service->create(array_merge($data, array('sucursal_id' => 27))); }, 409);
check((int) $pdo->query("SELECT store_pickup FROM folios WHERE orden = 'INV-1'")->fetchColumn() === 25, 'Retry with a different branch cannot overwrite the existing folio');
$pdo->exec("UPDATE registro_usuario SET id_cliente_invitado = 2 WHERE orden = 'INV-1' AND id_producto = 1");
rejects(function () use ($service, $data) { $service->create($data); }, 409);
$pdo->exec("UPDATE registro_usuario SET id_cliente_invitado = 1 WHERE orden = 'INV-1' AND id_producto = 1");
$products->rows[3] = product(3, 50);
$pdo->exec('UPDATE carrito SET id_producto = 3 WHERE id = 2');
rejects(function () use ($service, $data) { $service->create($data); }, 409);
check((int) $pdo->query("SELECT COUNT(*) FROM registro_usuario WHERE orden = 'INV-1' AND id_producto = 2")->fetchColumn() === 1, 'Same-total retry with different products cannot overwrite original lines');
$pdo->exec('UPDATE carrito SET id_producto = 2 WHERE id = 2');
$pdo->exec("DELETE FROM registro_usuario WHERE orden = 'INV-1'");
check($service->create($data)['folio_id'] === $result['folio_id'], 'Step-2 folio without items is completed without duplicating its header');
check((int) $pdo->query("SELECT COUNT(*) FROM registro_usuario WHERE orden = 'INV-1'")->fetchColumn() === 2, 'Previous empty folio receives its complete product detail');
$products->rows[1]['nombre'] = str_repeat('x', 251);
rejects(function () use ($service, $data) { $service->create($data); }, 422);
$products->rows[1]['nombre'] = 'Producto 1';
$products->rows[1]['codigo'] = "COD-\xF0\x9F\x92\x85";
rejects(function () use ($service, $data) { $service->create($data); }, 422);
$products->rows[1]['codigo'] = 'COD-1';
$pdo->exec('UPDATE carrito SET cantidad = 4 WHERE id = 1');
rejects(function () use ($service, $data) { $service->create($data); }, 409);
check((float) $pdo->query('SELECT total FROM folios WHERE id = 1')->fetchColumn() === 350.0, 'Conflicting retry never overwrites the existing folio');
$products->rows[1]['estatus'] = 0;
rejects(function () use ($service, $data) { $service->create($data); }, 409);
$products->rows[1]['estatus'] = 1;
$pdo->exec('UPDATE carrito SET activo = 0 WHERE guest_hash = ' . $pdo->quote($owner['guest_hash']));
rejects(function () use ($service, $data) { $service->create($data); }, 409);
$pdo->exec('UPDATE carrito SET activo = 1 WHERE guest_hash = ' . $pdo->quote($owner['guest_hash']));
$priceBeforeFailure = $pdo->query('SELECT precio FROM carrito WHERE id = 1')->fetchColumn();
$products->rows[1]['preciou'] = 200;
$pdo->exec("CREATE TRIGGER fail_folio BEFORE INSERT ON folios WHEN NEW.nombres = 'FALLAR Pérez' BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
try { $service->create(array_merge($data, array('nombre' => 'FALLAR'))); throw new RuntimeException('Expected SQL failure'); }
catch (PDOException $expected) {}
check(!$pdo->inTransaction() && !$locks, 'Failures roll back and release both order and cart locks');
check($pdo->query('SELECT precio FROM carrito WHERE id = 1')->fetchColumn() === $priceBeforeFailure, 'Failed folio rolls back its cart repricing in the same transaction');
check((int) $pdo->query('SELECT COUNT(*) FROM folios')->fetchColumn() === 2, 'Storage failure creates no partial folio');
$pdo->exec("CREATE TRIGGER fail_second_line BEFORE INSERT ON registro_usuario WHEN NEW.id_producto = 2 BEGIN SELECT RAISE(ABORT, 'simulated line failure'); END");
try { $service->create(array_merge($data, array('nombre' => 'FALLA DETALLE'))); throw new RuntimeException('Expected line failure'); }
catch (PDOException $expected) {}
check((int) $pdo->query('SELECT COUNT(*) FROM folios')->fetchColumn() === 2, 'Second-line failure rolls back the newly inserted folio');
check((int) $pdo->query('SELECT COUNT(*) FROM registro_usuario')->fetchColumn() === 4, 'Second-line failure rolls back the first inserted line');
check(!$pdo->inTransaction() && !$locks, 'Line failures release order and cart locks');
check((int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Step 3 still preserves all cart rows');
$pdo->exec('DROP TRIGGER fail_second_line');
$recovered = $service->create(array_merge($data, array('nombre' => 'FALLA DETALLE')));
check($recovered['productos_registrados'] === 2, 'A failed write can be retried successfully');
$auth->user = array('id' => 23);
rejects(function () use ($service, $data) { $service->create($data); }, 409);
echo "Guest folio checks passed: real repositories and cart pricing, guest identity, carrier types, statuses, retries, rollback and intact carts.\n";
