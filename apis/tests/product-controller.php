<?php

// Request validation tests without database or network access.
namespace App\Http {
    class Response
    {
        public static function success($data, $status = 200) {
            return array('status' => $status, 'data' => $data);
        }
        public static function error($code, $message, $status) {
            return array('status' => $status, 'code' => $code);
        }
    }
}

namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/ProductRepository.php';
    require __DIR__ . '/../src/Services/ProductService.php';
    require __DIR__ . '/../src/Controllers/ProductController.php';

    class CapturedProductService extends \App\Services\ProductService
    {
        public function __construct() {}
        public function getProduct($id) { return array('producto' => array('id_producto' => $id)); }
        public function listProducts($collection, $page, $limit, $subcategoryId = null, $onlyAvailable = false, $categoryId = null) {
            return array($collection, $page, $limit, $subcategoryId, $onlyAvailable, $categoryId);
        }
    }
    function check($condition, $message) {
        if (!$condition) throw new \RuntimeException($message);
    }
    $controller = new \App\Controllers\ProductController(new CapturedProductService());
    $_GET = array();
    check($controller->index()['data'] === array('recientes', 1, 20, null, false, null), 'Existing default preserved');
    $_GET = array('id_subcategoria' => '7');
    check($controller->index()['data'] === array('recientes', 1, 30, 7, false, null), 'Subcategory defaults to 30');
    $_GET = array('id_subcategoria' => '7', 'page' => '2', 'limit' => '30');
    check($controller->index()['data'] === array('recientes', 2, 30, 7, false, null), 'Second filtered page');
    foreach (array('0', '-1', '1 OR 1=1', array('7'), '2147483648') as $invalid) {
        $_GET = array('id_subcategoria' => $invalid);
        check($controller->index()['status'] === 422, 'Reject invalid subcategory');
    }
    $_GET = array('id_subcategoria' => '7', 'limit' => '101');
    check($controller->index()['status'] === 422, 'Reject excessive limit');
    $_GET = array('id_subcategoria' => '7', 'page' => '0');
    check($controller->index()['status'] === 422, 'Reject invalid page');
    $_GET = array('id_subcategoria' => '7', 'solo_disponibles' => '1');
    check($controller->index()['data'] === array('recientes', 1, 30, 7, true, null), 'Home available filter');
    $_GET = array('solo_disponibles' => '2');
    check($controller->index()['status'] === 422, 'Invalid availability rejected');
    $_GET = array('id_categoria' => '13');
    check($controller->index()['data'] === array('recientes', 1, 30, null, false, 13), 'Category defaults to 30');
    $_GET = array('id_categoria' => '13', 'id_subcategoria' => '7');
    check($controller->index()['data'] === array('recientes', 1, 30, 7, false, 13), 'Category and subcategory filters');
    foreach (array('0', '-1', '1 OR 1=1', array('13')) as $invalid) {
        $_GET = array('id_categoria' => $invalid);
        check($controller->index()['status'] === 422, 'Invalid category rejected');
    }
    $_GET = array('id_producto' => '17');
    check($controller->index()['data']['producto']['id_producto'] === 17, 'Detail request resolves ID');
    foreach (array('0', '-1', '17 OR 1=1', array('17')) as $invalid) {
        $_GET = array('id_producto' => $invalid);
        check($controller->index()['status'] === 422, 'Invalid product ID rejected');
    }
    echo "Product request validation tests passed.\n";
}
