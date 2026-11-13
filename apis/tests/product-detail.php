<?php

require __DIR__ . '/../src/Exceptions/ApiException.php';
require __DIR__ . '/../src/Repositories/ProductRepository.php';
require __DIR__ . '/../src/Services/ProductService.php';

class DetailRepository extends \App\Repositories\ProductRepository
{
    public $row;
    public $images = array();
    public $related = array();
    public $relatedArguments;
    public $variants = array();
    public $variantCodes = array();
    public function productVariants($code) {
        $this->variantCodes[] = $code;
        return array_values(array_filter($this->variants, function ($row) use ($code) {
            return $row['active'] === 1 && $row['codigo'] === $code;
        }));
    }
    public function relatedProducts($subcategoryId, $excludedId, $limit) {
        $this->relatedArguments = array($subcategoryId, $excludedId, $limit);
        return $this->related;
    }
    public function __construct() {}
    public function findProduct($id) {
        return $this->row && $this->row['id'] === $id && $this->row['active'] === 1 ? $this->row : false;
    }
    public function productImages($id) { return $this->images; }
}
function check($condition, $message) {
    if (!$condition) throw new \RuntimeException($message);
}
$repo = new DetailRepository();
$repo->row = array(
    'id' => 17, 'active' => 1, 'oculto' => 1, 'estatus' => 0,
    'nombre' => 'Gel', 'codigo' => 'gel-17', 'descripcion' => 'Descripcion',
    'preciou' => '100.00', 'descuento_general' => '80.00', 'descuento' => 'si', 'precio_costo' => '70.00',
    'imagen' => 'foto principal.jpg', 'preciom' => '75', 'precioc' => '70',
    'topem' => 6, 'topec' => 12, 'visitas' => 123, 'minimo' => 1,
    'sub_categoria' => 44, 'id_categoria' => 13, 'nombre_categoria' => 'Reveur',
    'nombre_subcategoria' => 'Esmaltes', 'promo' => 0, 'barcode' => null, 'product_nuevo' => 0,
);
$repo->images = array(array('a' => 'foto principal.jpg'), array('a' => ''), array('a' => 'segunda.jpg'));
$service = new \App\Services\ProductService($repo);
$related = $repo->row;
$related['id'] = 18;
$related['nombre'] = 'Otro gel';
$repo->related = array($related);
$variant = $repo->row;
$variant['id'] = 19;
$variant['estatus'] = 1;
$variant['nombre'] = 'Gel rosa';
$wrongCode = $variant;
$wrongCode['id'] = 20;
$wrongCode['codigo'] = 'otro-codigo';
$inactive = $variant;
$inactive['id'] = 21;
$inactive['active'] = 0;
$repo->variants = array($repo->row, $variant, $wrongCode, $inactive);
$data = $service->getProduct(17)['producto'];
check($repo->variantCodes === array('gel-17'), 'Variants requested by exact product code');
check(array_column($data['variantes'], 'id_producto') === array(17, 19), 'Only active products sharing code included, including current product');
check($data['variantes'][0]['disponible'] === false && $data['variantes'][1]['disponible'] === true, 'Variants preserve individual stock status');
check($repo->relatedArguments === array(44, 17, 10), 'Related query uses same subcategory, excludes current ID and limits to 10');
check(count($data['relacionados']) === 1 && $data['relacionados'][0]['id_producto'] === 18, 'Related products normalized');
check($data['precio_final'] === 80.0 && $data['ahorro'] === 20.0, 'Discount price and savings');
check($data['estatus'] === 0 && $data['disponible'] === false, 'Sold-out active product is accessible');
check(count($data['imagenes']) === 2, 'Images deduplicated and empty images omitted');
check(strpos($data['imagenes'][0], 'foto%20principal.jpg') !== false, 'Image filename encoded');
check($data['categoria']['id'] === 13 && $data['subcategoria']['id'] === 44, 'Navigation context');
check(!isset($data['active']) && !isset($data['oculto']), 'Return only public fields');
check($data['es_vip'] === false && $data['puede_comprar'] === false, 'Guest cannot order sold out');
$normal = new \App\Services\ProductService($repo, array('id' => 1, 'user_vip' => 0));
check($normal->getProduct(17)['producto']['precio_final'] === 80.0, 'Normal customer keeps offer');
$vip = new \App\Services\ProductService($repo, array('id' => 2, 'user_vip' => '1'));
$vipData = $vip->getProduct(17)['producto'];
check($vipData['precio_final'] === 80.0, 'Offer 80 beats VIP price 80.50');
check($vipData['es_vip'] === true && $vipData['puede_comprar'] === true && $vipData['disponible'] === false, 'VIP can order sold out, stock status unchanged');
check(!array_key_exists('precio_costo', $vipData), 'Cost never exposed');
check($vipData['variantes'][0]['es_vip'] && $vipData['relacionados'][0]['es_vip'], 'VIP pricing applies to variants and related products');
check(!array_key_exists('precio_costo', $vipData['variantes'][0]), 'Variant cost never exposed');
$repo->row['precio_costo'] = '50.00';
check($vip->getProduct(17)['producto']['precio_final'] === 57.5, 'VIP price 57.50 beats offer 80');
$repo->row['descuento'] = 'no';
check($vip->getProduct(17)['producto']['precio_final'] === 57.5, 'No offer uses cost times 1.15');
check($normal->getProduct(17)['producto']['precio_final'] === 100.0, 'Normal customer retail unchanged');
$repo->row['precio_costo'] = '71.23';
check($vip->getProduct(17)['producto']['precio_final'] === 81.91, 'VIP money rounds to cents');
$repo->row['precio_costo'] = '0';
check($vip->getProduct(17)['producto']['precio_final'] === 0.0, 'Zero cost follows same formula');
$repo->row['precio_costo'] = null;
try {
    $vip->getProduct(17);
    throw new \RuntimeException('Missing cost must not produce fabricated VIP price');
} catch (\App\Exceptions\ApiException $error) {
    check($error->getErrorCodeName() === 'VIP_PRICE_UNAVAILABLE', 'Missing cost handled explicitly');
}
$repo->row['precio_costo'] = '70';
$repo->row['imagen'] = null;
$repo->images = array();
check($service->getProduct(17)['producto']['imagenes'] === array(), 'Missing images supported');
$repo->row['sub_categoria'] = null;
check($service->getProduct(17)['producto']['relacionados'] === array(), 'No related query without subcategory');
$repo->row['codigo'] = ' ';
$previousCalls = count($repo->variantCodes);
check($service->getProduct(17)['producto']['variantes'] === array(), 'Blank code does not group unrelated products');
check(count($repo->variantCodes) === $previousCalls, 'Skip variant query for blank codes');
foreach (array(999, 17) as $id) {
    if ($id === 17) $repo->row['active'] = 0;
    try {
        $service->getProduct($id);
        throw new \RuntimeException('Unavailable product must fail');
    } catch (\App\Exceptions\ApiException $error) {
        check($error->getStatus() === 404, 'Inactive and missing products return 404');
    }
}
echo "Product detail tests passed.\n";
