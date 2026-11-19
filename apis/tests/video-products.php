<?php
namespace App\Http {
    class Response {
        public static function success($data, $status = 200) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/ProductRepository.php';
    require __DIR__ . '/../src/Services/ProductService.php';
    require __DIR__ . '/../src/Controllers/ProductController.php';
    function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
    $pdo = new \PDO('sqlite::memory:');
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE web_tiktok_videos (id INTEGER PRIMARY KEY, titulo TEXT, activo INTEGER);
        CREATE TABLE web_video_productos (video_id INTEGER, producto_id INTEGER, posicion INTEGER DEFAULT 0, PRIMARY KEY(video_id, producto_id));
        CREATE TABLE productos (id INTEGER PRIMARY KEY, active INTEGER DEFAULT 1, estatus INTEGER DEFAULT 1, oculto INTEGER DEFAULT 1,
            nombre TEXT DEFAULT 'Producto', codigo TEXT DEFAULT 'P-1', descripcion TEXT DEFAULT '', preciou TEXT DEFAULT '100',
            preciom TEXT DEFAULT '90', precioc TEXT DEFAULT '80', precio_costo TEXT DEFAULT '50', descuento TEXT DEFAULT 'si',
            descuento_general TEXT DEFAULT '80', visitas INTEGER DEFAULT 0, minimo INTEGER DEFAULT 1,
            sub_categoria INTEGER, promo INTEGER DEFAULT 0, barcode TEXT, product_nuevo INTEGER DEFAULT 0);
        CREATE TABLE img (id_producto INTEGER, posicion INTEGER, a TEXT);
        INSERT INTO web_tiktok_videos VALUES (1, 'Tutorial de uñas', 1), (2, 'Otro video', 1), (3, 'Oculto', 0), (4, 'Sin productos', 1);
        INSERT INTO productos (id) VALUES (1), (2), (3), (4), (5);
        UPDATE productos SET active = 0 WHERE id = 3;
        UPDATE productos SET estatus = 0 WHERE id = 2;
        INSERT INTO img VALUES (1, 2, 'segunda.jpg'), (1, 1, 'primera.jpg');
        INSERT INTO web_video_productos VALUES (1, 1, 2), (1, 2, 1), (1, 3, 0), (1, 4, 1), (2, 5, 0), (3, 1, 0);");
    $repo = new \App\Repositories\ProductRepository($pdo);
    $service = new \App\Services\ProductService($repo);
    $controller = new \App\Controllers\ProductController($service);
    $first = $service->listVideoProducts(1, 1, 2);
    check(array_column($first['productos'], 'id') === array(4, 1), 'Available linked products first, with position and ID determining stable order');
    check($first['video']['titulo'] === 'Tutorial de uñas' && $first['pagination']['total'] === 3 && $first['pagination']['total_pages'] === 2 && $first['pagination']['has_more'], 'Correct video metadata, total and pagination');
    check(substr($first['productos'][1]['imagen'], -12) === '/primera.jpg', 'One primary image without duplicate product rows');
    $second = $service->listVideoProducts(1, 2, 2);
    check(count($second['productos']) === 1 && $second['productos'][0]['id'] === 2 && !$second['productos'][0]['puede_comprar'], 'Sold out active product remains visible to regular customers');
    check(!$second['pagination']['has_more'], 'Final page has no next page');
    check($service->listVideoProducts(2, 1, 30)['productos'][0]['id'] === 5, 'Videos cannot mix products');
    check($service->listVideoProducts(4, 1, 30)['productos'] === array(), 'Valid empty video distinguishes empty state from missing video');
    foreach (array(3, 99) as $id) {
        try { $service->listVideoProducts($id, 1, 30); throw new \RuntimeException('Accepted missing/inactive video'); }
        catch (\App\Exceptions\ApiException $error) { check($error->getStatus() === 404, 'Inactive and missing videos are unavailable'); }
    }
    try { $pdo->exec('INSERT INTO web_video_productos VALUES (1, 1, 99)'); throw new \RuntimeException('Duplicate association accepted'); }
    catch (\PDOException $error) {}
    $vip = (new \App\Services\ProductService($repo, array('user_vip' => 1)))->listVideoProducts(1, 1, 30)['productos'];
    check($vip[0]['precio_final'] === 57.5 && $vip[2]['puede_comprar'] && $vip[2]['es_vip'], 'VIP price and sold out purchase rules remain server controlled');
    check(!isset($vip[0]['precio_costo']) && $first['productos'][0]['precio_final'] === 80.0, 'Cost never exposed and normal offers preserved');
    $_GET = array('id_video' => '1', 'page' => '1', 'limit' => '2', 'solo_disponibles' => '1');
    check($controller->index()['data']['pagination']['total'] === 3, 'Interior video list retains sold-out products regardless of home filter');
    foreach (array('0', '-1', 'abc', array('1'), '2147483648') as $invalid) {
        $_GET = array('id_video' => $invalid);
        check($controller->index()['status'] === 422, 'Invalid video IDs rejected');
    }
    $_GET = array('id_video' => '99');
    check($controller->index()['status'] === 404, 'Missing video returns API 404');
    echo "Video products tests passed: association uniqueness, isolation, pagination, active status, sold-out availability, VIP, prices and validation.\n";
}
