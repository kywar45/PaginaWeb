<?php
require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Repositories/CartRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';
require __DIR__ . '/../src/Services/CartPricing.php';
require __DIR__ . '/../src/Services/CartService.php';
require __DIR__ . '/../src/Services/CartIdentity.php';

use App\Exceptions\ApiException;
use App\Services\CartService;

function check($value, $message) { if (!$value) throw new RuntimeException($message); }
function rejects(callable $callback, $status) {
    try { $callback(); } catch (ApiException $error) {
        check($error->getStatus() === $status, 'Expected status ' . $status);
        return;
    }
    throw new RuntimeException('Expected rejection');
}

class MemoryCarts extends App\Repositories\CartRepository {
    public $rows = array();
    public $lockedOwners;
    public $failOnSave = null;
    private $nextId = 1;
    public function __construct() {}
    public function ownerKey($owner) { return $owner['user_id'] ? 'u:' . $owner['user_id'] : 'g:' . $owner['guest_hash']; }
    public function withOwnerLock($owner, callable $callback) {
        $backup = $this->rows;
        try { return $callback(); } catch (Throwable $error) { $this->rows = $backup; throw $error; }
    }
    public function withOwnersLock($owners, callable $callback) {
        $this->lockedOwners = $owners;
        return $this->withOwnerLock($owners[0], $callback);
    }
    public function lines($owner) { return array_values($this->rows[$this->ownerKey($owner)] ?? array()); }
    public function deactivate($owner, $ids) {
        foreach ($ids as $id) unset($this->rows[$this->ownerKey($owner)][$id]);
    }
    public function save($owner, $rowId, $product, $quantity) {
        if ($product['id_producto'] === $this->failOnSave) throw new RuntimeException('Simulated database failure');
        $id = $rowId ?? $this->nextId++;
        $this->rows[$this->ownerKey($owner)][$id] = array('id' => $id,
            'id_producto' => $product['id_producto'], 'nombre' => $product['nombre'],
            'codigo' => $product['codigo'], 'precio' => $product['precio_final'], 'cantidad' => $quantity);
    }
}
class CartProducts extends App\Repositories\ProductRepository {
    public $rows = array();
    public function __construct() {}
    public function cartProducts($ids) { return array_values(array_intersect_key($this->rows, array_flip($ids))); }
}
function fixture($id, $code = 'GEL-01', $status = 1) {
    return array('id' => $id, 'codigo' => $code, 'nombre' => 'Color ' . $id,
        'descripcion' => '', 'preciou' => 100, 'preciom' => 80, 'precioc' => 60,
        'topem' => 6, 'topec' => 20, 'precio_costo' => 50,
        'descuento' => 'no', 'descuento_general' => 0, 'active' => 1, 'estatus' => $status,
        'imagen' => null, 'visitas' => 0, 'minimo' => 1, 'sub_categoria' => null,
        'promo' => 0, 'barcode' => null, 'product_nuevo' => 0);
}
$repo = new MemoryCarts();
$products = new CartProducts();
for ($id = 1; $id <= 10; $id++) $products->rows[$id] = fixture($id);
$products->rows[11] = fixture(11, 'OTRO');
$products->rows[12] = fixture(12, 'GEL-01', 0);
$products->rows[13] = fixture(13);
$products->rows[13]['active'] = 0;
$guestA = array('user_id' => 0, 'guest_hash' => str_repeat('a', 64));
$guestB = array('user_id' => 0, 'guest_hash' => str_repeat('b', 64));
$registered = array('user_id' => 23, 'guest_hash' => null);
$cart = new CartService($repo, $products, $guestA);
for ($id = 1; $id <= 10; $id++) {
    $snapshot = $cart->execute('agregar', $id, 2);
    $expectedPrice = $id < 3 ? 100 : ($id < 10 ? 80 : 60);
    foreach ($snapshot['items'] as $item) {
        check($item['precio_final'] == $expectedPrice, 'Reprice ALL colors at a threshold');
        check($item['piezas_codigo'] === $id * 2, 'Sum by code across distinct product IDs');
        check(!array_key_exists('precio_costo', $item), 'Cost is private');
    }
}
check(count($snapshot['items']) === 10 && $snapshot['total'] == 1200, 'Ten colors, twenty pieces at box price');
check($snapshot['ahorro_total'] == 800 && $snapshot['total_sin_descuentos'] == 2000, 'Box savings compared with regular unit prices');
check($snapshot['items'][0]['ahorro_total'] == 80, 'Line savings include quantity');
foreach ($repo->lines($guestA) as $row) check($row['precio'] == 60, 'Persist authoritative tier price in DB');
$snapshot = $cart->execute('eliminar', 10);
check($snapshot['total'] == 1440 && $snapshot['items'][0]['tipo_precio'] === 'mayoreo', 'Removing a color downgrades every color');
check($snapshot['ahorro_total'] == 360, 'Savings decrease and reprice when box threshold is lost');
$snapshot = $cart->execute('cantidad', 1, 1);
check($snapshot['items'][0]['piezas_codigo'] === 17, 'Set replaces quantity, add increments');
$cart->execute('agregar', 11, 1);
$snapshot = (new CartService($repo, $products, $guestA))->execute();
check($snapshot['items'][9]['precio_final'] == 100, 'Different code does not share tier');
check($snapshot['cantidad_total'] === 18, 'Another request restores the saved cart');
check((new CartService($repo, $products, $guestB))->execute()['items'] === array(), 'Guests are isolated');
$normal = new CartService($repo, $products, $registered, array('id' => 23, 'user_vip' => 0));
check($normal->execute()['items'] === array(), 'Login restores own cart without taking guest cart');
$normal->execute('agregar', 1, 1);
check((new CartService($repo, $products, $registered, array('id' => 23, 'user_vip' => 0)))->execute()['cantidad_total'] === 1, 'Registered cart survives a new request/device');
rejects(function () use ($normal) { $normal->execute('agregar', 12, 1); }, 409);
rejects(function () use ($normal) { $normal->execute('agregar', 13, 1); }, 409);
rejects(function () use ($normal) { $normal->execute('cantidad', 88, 1); }, 404);
foreach (array(0, -1, 1.5, '2', 10000) as $invalid) {
    rejects(function () use ($cart, $invalid) { $cart->execute('agregar', 1, $invalid); }, 422);
}
rejects(function () use ($cart) { $cart->execute('agregar', '1', 1); }, 422);
$vip = new CartService($repo, $products, $registered, array('id' => 23, 'user_vip' => 1));
$snapshot = $vip->execute('agregar', 12, 20);
check($snapshot['items'][0]['precio_final'] == 57.5 && $snapshot['items'][1]['puede_comprar'], 'VIP cost pricing and sold-out permission retained');
check($snapshot['ahorro_total'] == 892.5, 'VIP savings use final VIP price for all buyable pieces');
$products->rows[12]['descuento'] = 'si';
$products->rows[12]['descuento_general'] = 40;
$snapshot = $vip->execute();
check($snapshot['items'][1]['precio_final'] == 40, 'VIP receives lower offer');
check($snapshot['ahorro_total'] == 1242.5, 'VIP lower offer included once in savings');
$snapshot = $normal->execute();
check($snapshot['items'][0]['piezas_codigo'] === 1 && $snapshot['total'] == 100, 'Normal sold-out rows do not count toward tiers or total');
check($snapshot['ahorro_total'] == 0 && $snapshot['total_sin_descuentos'] == 100, 'Unavailable products do not inflate savings');
rejects(function () use ($normal) { $normal->execute('cantidad', 12, 21); }, 409);
$normal->execute('cantidad', 12, 19);
$normal->execute('eliminar', 12);
$products->rows[1]['descuento'] = 'si';
$products->rows[1]['descuento_general'] = 90;
$snapshot = $normal->execute('cantidad', 1, 20);
check($snapshot['items'][0]['precio_final'] == 90 && $snapshot['items'][0]['tipo_precio'] === 'oferta', 'Offer is not replaced by box price');
check($snapshot['ahorro_total'] == 200, 'Offer savings compared with regular unit price');
unset($products->rows[1]);
$snapshot = $normal->execute();
check(!$snapshot['items'][0]['puede_comprar'] && $snapshot['total'] == 0, 'Deleted product remains removable but excluded');
check($snapshot['ahorro_total'] == 0, 'Deleted products do not generate savings');
$normal->execute('eliminar', 1);
check($normal->execute()['items'] === array(), 'Remove persists');

// SQL ownership constraints, independent of any live database.
$scope = new ReflectionMethod(App\Repositories\CartRepository::class, 'scope');
$scope->setAccessible(true);
$params = array();
$sql = $scope->invokeArgs($repo, array($guestA, &$params));
check(strpos($sql, 'guest_hash = :guest_hash') !== false && $params[':owner_id'] === 0, 'Guest SQL requires both owner zero and token hash');
$params = array();
$sql = $scope->invokeArgs($repo, array($registered, &$params));
check(strpos($sql, 'guest_hash IS NULL') !== false && $params[':owner_id'] === 23, 'Registered SQL excludes all guest carts');

$identity = new App\Services\CartIdentity();
$_COOKIE['sn_guest_cart'] = str_repeat('a', 64);
$ownerA = $identity->resolve(null);
check($ownerA['user_id'] === 0 && $ownerA['guest_hash'] === hash('sha256', str_repeat('a', 64)), 'Store hash, never raw guest token');
$_COOKIE['sn_guest_cart'] = str_repeat('b', 64);
check($identity->resolve(null)['guest_hash'] !== $ownerA['guest_hash'], 'Two browsers get different owner identities');
check($identity->resolve(array('id' => 23)) === $registered, 'Authenticated identity takes priority over guest cookie');
$_COOKIE['sn_guest_cart'] = 'invalid';
$newOwner = $identity->resolve(null);
check(strlen($_COOKIE['sn_guest_cart']) === 64 && $newOwner['guest_hash'] === hash('sha256', $_COOKIE['sn_guest_cart']), 'Invalid/missing guest cookie is securely replaced');

$duplicateRepo = new MemoryCarts();
$duplicateRepo->rows['g:' . $guestA['guest_hash']] = array(
    100 => array('id' => 100, 'id_producto' => 2, 'nombre' => 'Antes', 'codigo' => 'GEL-01', 'precio' => 999, 'cantidad' => 2),
    101 => array('id' => 101, 'id_producto' => 2, 'nombre' => 'Antes', 'codigo' => 'GEL-01', 'precio' => 999, 'cantidad' => 4),
);
$duplicates = new CartService($duplicateRepo, $products, $guestA);
$snapshot = $duplicates->execute();
check(count($snapshot['items']) === 1 && $snapshot['items'][0]['quantity'] === 6 && $snapshot['items'][0]['precio_final'] == 80, 'Legacy duplicates consolidate and reprice instead of duplicating colors');
check(count($duplicateRepo->lines($guestA)) === 1, 'Duplicate DB rows deactivate');
rejects(function () use ($duplicates) { $duplicates->execute('agregar', 2, 9999); }, 422);
check($duplicates->execute()['cantidad_total'] === 6, 'Rejected change leaves the persisted cart intact');

// Fusion de login: ambos propietarios se guardan o se revierten juntos.
$mergeRepo = new MemoryCarts();
$products->rows[1] = fixture(1);
$visitor = new CartService($mergeRepo, $products, $guestA);
$account = new CartService($mergeRepo, $products, $registered, array('id' => 23, 'user_vip' => 0), $guestA);
$visitor->execute('agregar', 1, 2);
$visitor->execute('agregar', 2, 2);
$account->execute('agregar', 1, 2);
$snapshot = $account->execute('fusionar');
check($snapshot['fusion']['fusionado'] && $snapshot['fusion']['piezas'] === 4, 'Merge reports imported pieces');
check($snapshot['items'][0]['quantity'] === 4 && $snapshot['items'][1]['quantity'] === 2, 'Same product adds quantity, other color remains separate');
check($snapshot['total'] == 480, 'Combined code quantity crosses wholesale threshold');
check($mergeRepo->lockedOwners === array($registered, $guestA), 'Merge locks both owners');
check($visitor->execute()['items'] === array(), 'Successful commit empties visitor cart');
$snapshot = $account->execute('fusionar');
check(!$snapshot['fusion']['fusionado'] && $snapshot['cantidad_total'] === 6, 'Repeated login/merge does not duplicate pieces');
$visitor->execute('agregar', 3, 1);
$before = $mergeRepo->rows;
$mergeRepo->failOnSave = 3;
try { $account->execute('fusionar'); throw new RuntimeException('Expected DB failure'); }
catch (RuntimeException $error) { check($error->getMessage() === 'Simulated database failure', 'Expected save failure'); }
check($mergeRepo->rows === $before, 'Failure restores both carts, including prior account row updates');
$mergeRepo->failOnSave = null;
check($visitor->execute()['cantidad_total'] === 1, 'Failed merge retains visitor pieces for retry');
$snapshot = $account->execute('fusionar');
check($snapshot['cantidad_total'] === 7 && $visitor->execute()['items'] === array(), 'Retry merges preserved pieces once');
$visitor->execute('agregar', 4, 1);
$products->rows[4]['estatus'] = 0;
$snapshot = $account->execute('fusionar');
check($snapshot['fusion']['no_disponibles'] === 1 && !$snapshot['items'][3]['puede_comprar'], 'Unavailable visitor product remains visible with warning and no subtotal');
$visitor->execute('agregar', 5, 1);
$products->rows[5]['estatus'] = 0;
$vipMerge = new CartService($mergeRepo, $products, $registered, array('id' => 23, 'user_vip' => 1), $guestA);
$snapshot = $vipMerge->execute('fusionar');
check($snapshot['items'][4]['puede_comprar'] && $snapshot['items'][0]['precio_final'] == 57.5, 'VIP login reprices entire merged cart and allows sold-out colors');
rejects(function () use ($visitor) { $visitor->execute('fusionar'); }, 401);
$visitor->execute('agregar', 6, 9999);
$account->execute('agregar', 6, 1);
$before = $mergeRepo->rows;
rejects(function () use ($account) { $account->execute('fusionar'); }, 409);
check($mergeRepo->rows === $before, 'Quantity limit rejects merge without truncating or losing either cart');

$centProducts = new CartProducts();
$centProducts->rows[1] = fixture(1);
$centProducts->rows[1]['preciou'] = 100.01;
$centProducts->rows[1]['descuento'] = 'si';
$centProducts->rows[1]['descuento_general'] = 99.99;
$centCart = new CartService(new MemoryCarts(), $centProducts, $guestB);
check($centCart->execute('agregar', 1, 3)['ahorro_total'] == 0.06, 'Savings use integer cents, without floating point noise');
$centProducts->rows[1]['descuento_general'] = 101;
check($centCart->execute()['ahorro_total'] == 0, 'A higher final price never creates negative savings');
echo "Server cart tests passed: owner isolation, persistence, grouping, repricing, offers, VIP and stock.\n";
