<?php

// Pagination contract test without database or network access.
require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';

class PaginatedOffers extends \App\Repositories\ProductRepository
{
    public $rows;
    public function __construct() {}
    public function countOffers($onlyAvailable = false) { return count($this->listPage('ofertas', PHP_INT_MAX, 0, null, $onlyAvailable)); }
    public function countSubcategory($collection, $subcategoryId, $onlyAvailable = false)
    {
        return count($this->listPage($collection, PHP_INT_MAX, 0, $subcategoryId, $onlyAvailable));
    }
    public $categorySubcategories = array(13 => array(7, 9), 2 => array(44));
    public function countCategory($collection, $categoryId, $onlyAvailable = false, $subcategoryId = null)
    {
        return count($this->listPage($collection, PHP_INT_MAX, 0, $subcategoryId, $onlyAvailable, $categoryId));
    }
    public function listPage($collection, $limit, $offset, $subcategoryId = null, $onlyAvailable = false, $categoryId = null)
    {
        $rows = array_values(array_filter($this->rows, function ($row) use ($onlyAvailable) {
            return ($row['active'] ?? 1) === 1 && (!$onlyAvailable || $row['estatus'] === 1);
        }));
        if ($subcategoryId !== null) {
            $rows = array_values(array_filter($rows, function ($row) use ($subcategoryId) {
                return $row['sub_categoria'] === $subcategoryId;
            }));
        }
        if ($categoryId !== null) {
            $subcategories = $this->categorySubcategories[$categoryId] ?? array();
            $rows = array_values(array_filter($rows, function ($row) use ($subcategories) {
                return in_array($row['sub_categoria'], $subcategories, true);
            }));
        }
        return array_slice($rows, $offset, $limit);
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
foreach ($repo->rows as $index => $row) {
    $repo->rows[$index]['sub_categoria'] = $row['id'] % 2 === 0 ? 7 : 9;
}
$filtered = $service->listProducts('recientes', 1, 30, 7);
check(count($filtered['productos']) === 30, 'Subcategory page size');
check(array_unique(array_column($filtered['productos'], 'sub_categoria')) === array(7), 'Only requested subcategory');
check(!$filtered['pagination']['has_more'], 'Filtered last page');
check($filtered['pagination']['total'] === 30 && $filtered['pagination']['total_pages'] === 1, 'Filtered totals');
$filteredIds = array();
foreach (array(1, 2) as $page) {
    $result = $service->listProducts('recientes', $page, 30, 9);
    check($result['pagination']['total'] === 31, 'Total only counts selected subcategory');
    check($result['pagination']['total_pages'] === 2, 'Filtered page count');
    check($result['pagination']['has_more'] === ($page === 1), 'Filtered next page');
    $filteredIds = array_merge($filteredIds, array_column($result['productos'], 'id_producto'));
}
check($filteredIds === range(61, 1, -2), 'Every filtered product appears once');
$emptySubcategory = $service->listProducts('recientes', 1, 30, 999);
check($emptySubcategory['pagination']['total'] === 0 && $emptySubcategory['pagination']['total_pages'] === 0, 'Empty filtered totals');
check($service->listProducts('recientes', 1, 30, 999)['productos'] === array(), 'Empty subcategory');
$categoryIds = array();
foreach (array(1, 2, 3) as $page) {
    $result = $service->listProducts('recientes', $page, 30, null, false, 13);
    check($result['pagination']['total'] === 61 && $result['pagination']['total_pages'] === 3, 'Whole category totals');
    $categoryIds = array_merge($categoryIds, array_column($result['productos'], 'id'));
}
check($categoryIds === range(61, 1), 'Whole category spans subcategories with no duplicates');
check($service->listProducts('recientes', 1, 30, 7, false, 13)['pagination']['total'] === 30, 'Combined category and subcategory');
check($service->listProducts('recientes', 1, 30, 7, false, 2)['productos'] === array(), 'Mismatched category excludes unrelated subcategory');
check($service->listProducts('recientes', 1, 30, null, false, 999)['pagination']['total'] === 0, 'Empty category');
check($service->listProducts('ofertas', 4, 30)['productos'] === array(), 'Past last page');
$repo->rows = array_slice($repo->rows, 0, 60);
check(!$service->listProducts('ofertas', 2, 30)['pagination']['has_more'], 'Exact multiple');
$repo->rows = array();
$empty = $service->listProducts('ofertas', 1, 30);
check($empty['productos'] === array() && $empty['pagination']['total_pages'] === 0, 'Empty offers');
$repo->rows = array();
foreach (array(array(1, 1, 0), array(1, 1, 1), array(1, 0, 1), array(0, 1, 0)) as $index => $flags) {
    $row['id'] = $index + 1;
    $row['sub_categoria'] = 44;
    $row['active'] = $flags[0];
    $row['estatus'] = $flags[1];
    $row['oculto'] = $flags[2];
    $repo->rows[] = $row;
}
$catalog = $service->listProducts('recientes', 1, 30, 44);
check(count($catalog['productos']) === 3, 'Catalog includes sold out active products');
check($catalog['pagination']['total'] === 3, 'Catalog count matches visibility');
check($catalog['productos'][2]['disponible'] === false, 'Sold out marked unavailable');
$home = $service->listProducts('recientes', 1, 30, 44, true);
check(array_column($home['productos'], 'id') === array(1, 2), 'Home only available and ignores oculto');
check($home['pagination']['total'] === 2, 'Home count only available');
foreach ($repo->rows as $index => $item) $repo->rows[$index]['precio_costo'] = 50;
$vipService = new \App\Services\ProductService($repo, array('user_vip' => 1));
$vipCatalog = $vipService->listProducts('recientes', 1, 30, 44);
check(count($vipCatalog['productos']) === 3, 'VIP catalog still filters inactive products');
check($vipCatalog['productos'][2]['puede_comprar'] === true, 'Sold-out catalog product orderable for VIP');
check($vipCatalog['productos'][0]['precio_final'] === 57.5, 'VIP price applied to catalog');
$vipHome = $vipService->listProducts('recientes', 1, 30, 44, true);
check(count($vipHome['productos']) === 2, 'Home excludes sold-out products even for VIP');
echo "Product pagination and availability tests passed.\n";
