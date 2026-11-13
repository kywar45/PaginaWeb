<?php
namespace App\Http {
    class Response {
        public static function success($data, $status = 200) { return array('status' => $status, 'data' => $data); }
        public static function error($code, $message, $status) { return array('status' => $status, 'code' => $code); }
    }
}
namespace {
    require __DIR__ . '/../src/Exceptions/ApiException.php';
    require __DIR__ . '/../src/Repositories/BrandRepository.php';
    require __DIR__ . '/../src/Services/BrandService.php';
    require __DIR__ . '/../src/Controllers/BrandController.php';
    require __DIR__ . '/../src/Repositories/ProductRepository.php';
    require __DIR__ . '/../src/Services/ProductService.php';
    require __DIR__ . '/../src/Controllers/ProductController.php';

    function check($condition, $message) { if (!$condition) throw new \RuntimeException($message); }

    class Brands extends \App\Repositories\BrandRepository {
        public $rows = array();
        public function __construct() {}
        public function listActive() { return $this->rows; }
    }
    $brands = new Brands();
    $controller = new \App\Controllers\BrandController(new \App\Services\BrandService($brands));
    check($controller->index()['data']['marcas'] === array(), 'Empty brand list');
    $brands->rows = array(array('id' => '2', 'nombre' => 'Réveur', 'total_productos' => '31'));
    check($controller->index()['data']['marcas'][0] === array('id' => 2, 'nombre' => 'Réveur', 'logo' => null, 'total_productos' => 31), 'Brand fields and numeric values');
    $brands->rows[0]['logo'] = ' https://example.com/logos/reveur.png ';
    check($controller->index()['data']['marcas'][0]['logo'] === 'https://example.com/logos/reveur.png', 'Logo preserved and trimmed');
    $brands->rows[0]['logo'] = '  ';
    check($controller->index()['data']['marcas'][0]['logo'] === null, 'Empty logo uses fallback');
    $brands->rows[0]['logo'] = 'Logo/wing 500x500.png';
    check($controller->index()['data']['marcas'][0]['logo'] === 'https://www.importadorasupernova.com/Logo/wing%20500x500.png', 'Relative path, case and spaces');
    $brands->rows[0]['logo'] = '/Logo/wing%20500x500.png';
    check($controller->index()['data']['marcas'][0]['logo'] === 'https://www.importadorasupernova.com/Logo/wing%20500x500.png', 'Already encoded path is not double encoded');
    $brands->rows[0]['logo'] = 'javascript:alert(1)';
    check($controller->index()['data']['marcas'][0]['logo'] === null, 'Unsupported logo scheme rejected');

    class BrandProducts extends \App\Repositories\ProductRepository {
        public $arguments;
        public function __construct() {}
        public function countBrand($brandId, $onlyAvailable = false) { return $brandId === 2 ? 31 : 0; }
        public function brandPage($brandId, $limit, $offset, $onlyAvailable = false) {
            $this->arguments = array($brandId, $limit, $offset, $onlyAvailable);
            if ($brandId !== 2) return array();
            $rows = array();
            for ($id = 1; $id <= 31; $id++) $rows[] = array(
                'id' => $id, 'nombre' => 'Gel', 'codigo' => 'G-' . $id, 'descripcion' => '',
                'preciou' => 100, 'descuento_general' => 80, 'descuento' => 'si', 'imagen' => 'gel.jpg',
                'preciom' => 0, 'precioc' => 0, 'visitas' => 0, 'estatus' => $id === 31 ? 0 : 1,
                'minimo' => 1, 'sub_categoria' => null, 'promo' => 0, 'barcode' => null, 'product_nuevo' => 0,
            );
            return array_slice($rows, $offset, $limit);
        }
    }
    $repo = new BrandProducts();
    $controller = new \App\Controllers\ProductController(new \App\Services\ProductService($repo));
    $_GET = array('id_marca' => '2', 'page' => '2', 'limit' => '30');
    $data = $controller->index()['data'];
    check($repo->arguments === array(2, 30, 30, false), 'Brand and page passed to repository');
    check($data['marca_id'] === 2 && $data['pagination']['total'] === 31 && $data['pagination']['total_pages'] === 2, 'Brand response and totals');
    check(count($data['productos']) === 1 && !$data['productos'][0]['disponible'], 'Sold out product retained on last page');
    check($data['productos'][0]['precio_final'] === 80.0 && substr($data['productos'][0]['imagen'], -11) === '/31/gel.jpg', 'Offer and image normalization');
    $_GET = array('id_marca' => '3');
    check($controller->index()['data']['productos'] === array(), 'Unknown or empty brand');
    foreach (array('0', '-1', 'abc', array('2')) as $invalid) {
        $_GET = array('id_marca' => $invalid);
        check($controller->index()['status'] === 422, 'Invalid brand rejected');
    }
    echo "Brand contract tests passed.\n";
}
