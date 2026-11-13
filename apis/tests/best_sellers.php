<?php

require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';

class RankedProducts extends \App\Repositories\ProductRepository
{
    public $onlyAvailable;
    public function __construct() {}
    public function bestSellers($onlyAvailable = false, $subcategoryId = null, $categoryId = null)
    {
        $this->onlyAvailable = $onlyAvailable;
        $rows = array();
        // The best seller is sold out: its ranking must survive normalization.
        for ($id = 30; $id >= 1; $id--) {
            if ($onlyAvailable && $id === 30) continue;
            $rows[] = array(
                'id' => $id, 'nombre' => 'Producto ' . $id, 'codigo' => 'P-' . $id,
                'descripcion' => '', 'preciou' => '100', 'descuento_general' => '80',
                'descuento' => 'si', 'imagen' => 'principal.jpg', 'preciom' => 0, 'precioc' => 0,
                'visitas' => 0, 'estatus' => $id === 30 ? 0 : 1, 'minimo' => 1,
                'sub_categoria' => null, 'promo' => 0, 'barcode' => null, 'product_nuevo' => 0,
            );
        }
        return $rows;
    }
}

function check($condition, $message)
{
    if (!$condition) throw new \RuntimeException($message);
}

$repo = new RankedProducts();
$service = new \App\Services\ProductService($repo);
$first = $service->listProducts('mas_vendidos', 1, 20);
$second = $service->listProducts('mas_vendidos', 2, 20);
check(count($first['productos']) === 20 && count($second['productos']) === 10, 'Page sizes');
check($first['pagination']['total'] === 30 && $first['pagination']['total_pages'] === 2, 'Top 30 pagination');
check($first['pagination']['has_more'] && !$second['pagination']['has_more'], 'Next page state');
$ids = array_merge(array_column($first['productos'], 'id'), array_column($second['productos'], 'id'));
check($ids === range(30, 1), 'Ranking preserved across pages');
$product = $first['productos'][0];
check(!$product['disponible'] && $product['estatus'] === 0, 'Sold out best seller remains visible');
check($product['precio_final'] === 80.0 && $product['porcentaje_descuento'] === 20, 'Offer normalization');
check(substr($product['imagen'], -17) === '/30/principal.jpg', 'Product-specific image');
check($service->listProducts('mas_vendidos', 3, 20)['productos'] === array(), 'No extra products beyond ranking');
check($service->listProducts('mas_vendidos', 1, 30, null, true)['pagination']['total'] === 29 && $repo->onlyAvailable, 'Optional stock filter');
echo "Best seller tests passed.\n";
