<?php

// Pagination contract test without database or network access.
require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';

class PaginatedOffers extends \App\Repositories\ProductRepository
{
    public $rows;
    public function __construct() {}
    public function countOffers() { return count($this->rows); }
    public function listPage($collection, $limit, $offset)
    {
        return array_slice($this->rows, $offset, $limit);
    }
}

function check($condition, $message)
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

$repo = new PaginatedOffers();
$repo->rows = array();
for ($id = 61; $id >= 1; $id--) {
    $repo->rows[] = array(
        'id' => $id, 'nombre' => 'Oferta ' . $id, 'codigo' => (string) $id,
        'descripcion' => '', 'preciou' => '100', 'descuento_general' => '80',
        'descuento' => 'si', 'imagen' => null, 'preciom' => 0, 'precioc' => 0,
        'visitas' => 0, 'estatus' => 1, 'minimo' => 1, 'sub_categoria' => null,
        'promo' => 0, 'barcode' => null, 'product_nuevo' => 0,
    );
}
$service = new \App\Services\ProductService($repo);
$ids = array();
foreach (array(1, 2, 3) as $page) {
    $result = $service->listProducts('ofertas', $page, 30);
    check(count($result['productos']) === ($page === 3 ? 1 : 30), 'Page size');
    check($result['pagination']['total'] === 61, 'Total count');
    check($result['pagination']['total_pages'] === 3, 'Total pages');
    check($result['pagination']['has_more'] === ($page < 3), 'Next page availability');
    check($result['pagination']['next_page'] === ($page < 3 ? $page + 1 : null), 'Next page');
    $ids = array_merge($ids, array_column($result['productos'], 'id_producto'));
}
check($ids === range(61, 1), 'Every offer appears exactly once across pages');
check($service->listProducts('ofertas', 4, 30)['productos'] === array(), 'Past last page');
$repo->rows = array_slice($repo->rows, 0, 60);
check(!$service->listProducts('ofertas', 2, 30)['pagination']['has_more'], 'Exact multiple');
$repo->rows = array();
$empty = $service->listProducts('ofertas', 1, 30);
check($empty['productos'] === array() && $empty['pagination']['total_pages'] === 0, 'Empty offers');
echo "Product pagination tests passed.\n";
