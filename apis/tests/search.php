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

    class SearchProducts extends \App\Repositories\ProductRepository {
        public $arguments;
        public function __construct() {}
        public $countArguments;
        public function countSearch($query, $onlyAvailable = false, $offersOnly = false) { $this->countArguments = array($query, $onlyAvailable, $offersOnly); return 31; }
        public function searchPage($query, $limit, $offset, $sort = 'relevancia', $onlyAvailable = false, $offersOnly = false, $isVip = false) {
            $this->arguments = array($query, $limit, $offset, $sort, $onlyAvailable, $offersOnly);
            return array(array('id' => 1, 'nombre' => 'Gel rosa', 'codigo' => 'LA-317',
                'descripcion' => '', 'preciou' => 100, 'descuento_general' => 0, 'descuento' => 'no',
                'imagen' => null, 'preciom' => 0, 'precioc' => 0, 'visitas' => 0, 'estatus' => 0,
                'minimo' => 1, 'sub_categoria' => null, 'promo' => 0, 'barcode' => null, 'product_nuevo' => 0));
        }
    }
    function check($value, $message) { if (!$value) throw new \RuntimeException($message); }
    $repo = new SearchProducts();
    $controller = new \App\Controllers\ProductController(new \App\Services\ProductService($repo));
    $_GET = array('q' => "  gel   rosa  ", 'page' => '2', 'limit' => '30');
    $result = $controller->index();
    check($result['status'] === 200 && $repo->arguments === array('gel rosa', 30, 30, 'relevancia', false, false), 'Normalize query and paginate');
    check($result['data']['search_query'] === 'gel rosa', 'Search response marker');
    check($result['data']['pagination']['total_pages'] === 2 && !$result['data']['pagination']['has_more'], 'Last search page');
    check($result['data']['productos'][0]['estatus'] === 0, 'Keep sold-out result for availability badge');
    $_GET = array('q' => 'gel', 'orden' => 'precio_asc', 'solo_ofertas' => '1', 'solo_disponibles' => '1');
    $result = $controller->index();
    check($repo->arguments === array('gel', 20, 0, 'precio_asc', true, true), 'Propagate filters before pagination');
    check($repo->countArguments === array('gel', true, true), 'Count uses same filters');
    check($result['data']['search_filters'] === array('orden' => 'precio_asc', 'solo_disponibles' => true, 'solo_ofertas' => true), 'Echo effective filters');
    foreach (array(array('orden' => 'precio; DROP TABLE productos'), array('orden' => array('precio_asc')), array('solo_ofertas' => '2'), array('solo_disponibles' => 'si')) as $invalidFilters) {
        $_GET = array_merge(array('q' => 'gel'), $invalidFilters);
        check($controller->index()['status'] === 422, 'Reject invalid filters');
    }
    foreach (array('', '   ', array('gel'), str_repeat('a', 81), "\xff") as $invalid) {
        $_GET = array('q' => $invalid);
        check($controller->index()['status'] === 422, 'Reject invalid query');
    }
    $filter = new \ReflectionMethod(\App\Repositories\ProductRepository::class, 'searchFilter');
    $filter->setAccessible(true);
    list($sql, $params) = $filter->invoke($repo, "50%_! 'OR' ");
    check(strpos($sql, 'p.active = 1') !== false && strpos($sql, 'oculto') === false, 'Publication rules');
    check(strpos($sql, "'OR'") === false && $params[':text_0'] === '%50!%!_!!%', 'Literal wildcards and parameterized user text');
    list($sql) = $filter->invoke($repo, 'gel', true, true);
    check(strpos($sql, 'p.estatus = 1') !== false && strpos($sql, 'descuento_general') !== false, 'Stock and effective offers in SQL');
    $order = new \ReflectionMethod(\App\Repositories\ProductRepository::class, 'searchOrder');
    $order->setAccessible(true);
    foreach (array('precio_asc' => 'ASC', 'precio_desc' => 'DESC') as $key => $direction) {
        $sql = $order->invoke($repo, $key);
        check(strpos($sql, 'descuento_general') !== false && strpos($sql, 'preciou') !== false && strpos($sql, 'DECIMAL(12,2)') !== false, 'Sort by numeric effective price');
        check(strpos($sql, 'END ' . $direction) !== false, 'Correct sort direction');
        check(substr($sql, -8) === 'p.id ASC', 'Stable pagination tie breaker');
    }
    $sql = $order->invoke($repo, 'precio_asc', true);
    check(strpos($sql, 'precio_costo') !== false && strpos($sql, 'LEAST(') !== false && strpos($sql, 'ROUND(') !== false, 'VIP sorting uses rounded cost and lower offer');
    echo "Search contract tests passed.\n";
}
