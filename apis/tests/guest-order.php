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
$pdo->exec('ALTER TABLE web_clientes_invitados ADD COLUMN checkout_request_hash TEXT DEFAULT NULL');
$pdo->exec('CREATE UNIQUE INDEX uk_guest_checkout_request ON web_clientes_invitados(checkout_request_hash)');
$pdo->exec('CREATE TABLE web_pedidos_envio (id INTEGER PRIMARY KEY AUTOINCREMENT,
    folio_id INTEGER NOT NULL UNIQUE REFERENCES folios(id), nombre TEXT NOT NULL, apellido TEXT NOT NULL,
    correo TEXT NOT NULL, estado TEXT NOT NULL, ciudad TEXT NOT NULL, colonia TEXT NOT NULL,
    direccion TEXT NOT NULL, codigo_postal TEXT NOT NULL, telefono TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
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
$_COOKIE['sn_guest_cart'] = str_repeat('d', 64);
$data['request_id'] = str_repeat('a', 32);
function refill($pdo) { $pdo->exec('UPDATE carrito SET activo = 1 WHERE id IN (1,2)'); }
function counts($pdo) {
    return array_map(function ($table) use ($pdo) { return (int) $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(); }, array('folios', 'registro_usuario', 'web_pedidos_envio'));
}
$result = $service->create($data);
$folio = $pdo->query('SELECT * FROM folios')->fetch(PDO::FETCH_ASSOC);
check($folio['id_usuario'] === null && $folio['nombres'] === 'Ana Pérez', 'Guest identity and name');
check($folio['orden'] === 'INV-' . $result['cliente_invitado_id'] && (int) $folio['shipping_carrier_id'] === 21, 'Consecutive reference and personal carrier');
check((int) $folio['store_pickup'] === 25 && (int) $folio['cantidad'] === 5 && (float) $folio['total'] === 350.0, 'Delivery and authoritative cart totals');
check((int) $folio['order_status_id'] === 1 && $folio['estatus_ventas'] === 'no contactado', 'Initial statuses');
check($result['carrito_vaciado'] === true && (int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 1, 'Only the purchasing guest cart is emptied');
check((int) $pdo->query('SELECT activo FROM carrito WHERE id = 3')->fetchColumn() === 1, 'Foreign guest cart remains active');
$lines = $pdo->query('SELECT * FROM registro_usuario ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
check(count($lines) === 2 && (float) $lines[0]['precio'] === 100.0 && (int) $lines[0]['cantidad'] === 2, 'Historical products saved before clearing cart');
check($lines[0]['id_usuario'] === null && $lines[0]['orden'] === $folio['orden'] && $lines[0]['direcciond'] === '', 'Guest ownership and unchanged legacy fields');
$shipping = $pdo->query('SELECT * FROM web_pedidos_envio')->fetch(PDO::FETCH_ASSOC);
check((int) $shipping['folio_id'] === $result['folio_id'] && $shipping['direccion'] === $data['direccion'] && $shipping['correo'] === $data['correo'], 'Shipping snapshot saved');
check($service->create($data)['folio_id'] === $result['folio_id'] && counts($pdo) === array(1,2,1), 'Lost-response retry succeeds with an empty cart, without duplicates');
refill($pdo);
check($service->create($data)['folio_id'] === $result['folio_id'], 'Retry still returns its existing order after new additions');
check((int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Retry never clears products added after the original purchase');
$second = $service->create(array_merge($data, array('request_id' => str_repeat('b', 32))));
check($second['cliente_invitado_id'] !== $result['cliente_invitado_id'] && $second['orden'] === 'INV-' . $second['cliente_invitado_id'], 'Identical contact details can place another purchase with consecutive guest ID');
check(counts($pdo) === array(2,4,2), 'Separate purchases retain separate products and shipping');
refill($pdo);
$national = array_merge($data, array('request_id' => str_repeat('c', 32), 'tipo_entrega' => 1, 'shipping_carrier_id' => 8, 'sucursal_id' => null));
$third = $service->create($national);
check($third['total'] === 350 && $pdo->query('SELECT store_pickup FROM folios WHERE id = ' . $third['folio_id'])->fetchColumn() === null, 'National shipping retains carrier selection');
refill($pdo);
$invalid = array_merge($data, array('request_id' => str_repeat('f', 32)));
rejects(function () use ($service, $invalid) { $service->create(array_merge($invalid, array('sucursal_id' => 26))); }, 409);
rejects(function () use ($service, $invalid) { $service->create(array_merge($invalid, array('sucursal_id' => 28))); }, 409);
rejects(function () use ($service, $invalid) { $service->create(array_merge($invalid, array('tipo_entrega' => 1, 'shipping_carrier_id' => 9))); }, 409);
check((int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Validation failure retains cart');
$before = counts($pdo);
$pdo->exec("CREATE TRIGGER fail_shipping BEFORE INSERT ON web_pedidos_envio BEGIN SELECT RAISE(ABORT, 'shipping failure'); END");
try { $service->create($invalid); throw new RuntimeException('Expected shipping failure'); } catch (PDOException $expected) {}
check(counts($pdo) === $before && (int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Shipping failure rolls back order, products and cart');
$pdo->exec('DROP TRIGGER fail_shipping');
$pdo->exec("CREATE TRIGGER fail_clear BEFORE UPDATE OF activo ON carrito WHEN NEW.activo = 0 AND OLD.id = 1 BEGIN SELECT RAISE(ABORT, 'cart failure'); END");
try { $service->create($invalid); throw new RuntimeException('Expected cart failure'); } catch (PDOException $expected) {}
check(counts($pdo) === $before && (int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Cart clearing failure rolls back the whole purchase');
check(!$pdo->inTransaction() && !$locks, 'Failure releases locks and transaction');
$pdo->exec('DROP TRIGGER fail_clear');
$recovered = $service->create($invalid);
check($recovered['carrito_vaciado'] === true && (int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 1, 'Failed purchase can retry and finish normally');
refill($pdo);
$products->rows[1]['estatus'] = 0;
rejects(function () use ($service, $data) { $service->create(array_merge($data, array('request_id' => str_repeat('e', 32)))); }, 409);
check((int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Unavailable product prevents cart clearing');
$products->rows[1]['estatus'] = 1;
$pdo->exec("CREATE TRIGGER fail_item BEFORE INSERT ON registro_usuario WHEN NEW.id_producto = 2 BEGIN SELECT RAISE(ABORT, 'item failure'); END");
$before = counts($pdo);
try { $service->create(array_merge($data, array('request_id' => str_repeat('e', 32)))); throw new RuntimeException('Expected item failure'); } catch (PDOException $expected) {}
check(counts($pdo) === $before && (int) $pdo->query('SELECT COUNT(*) FROM carrito WHERE activo = 1')->fetchColumn() === 3, 'Product failure rolls back partial lines and retains cart');
$pdo->exec('DROP TRIGGER fail_item');
$auth->user = array('id' => 23);
rejects(function () use ($service, $data) { $service->create($data); }, 409);
echo "Guest order checks passed: complete purchase, shipping, consecutive folios, scoped cart clearing, lost-response retries and full rollback.\n";
