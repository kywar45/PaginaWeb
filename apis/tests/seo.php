<?php

require __DIR__ . '/../../public/seo/PageMetadata.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$home = 'https://www.importadorasupernova.com/ImportadoraSupernova/';
$product = array('id_producto' => 42, 'nombre' => 'Gel Rosa - Brillo', 'descripcion' => '<p>Gel para uñas</p>',
    'codigo' => 'GEL-42', 'imagen' => 'https://www.importadorasupernova.com/imagenes/42/foto.png',
    'imagenes' => array('https://www.importadorasupernova.com/imagenes/42/foto.png'),
    'precio_publico' => 80, 'precio_final' => 57.50, 'es_vip' => true, 'estatus' => 0, 'visitas' => 9999);
$meta = SupernovaPageMetadata::build('/producto/42/otro', $home, $product);
check($meta['title'] === 'Gel Rosa - Brillo | Importadora Supernova', 'Product-specific title');
check($meta['url'] === $home . 'producto/42/gel-rosa-brillo', 'Product canonical uses actual ID and name');
check($meta['graph'][0]['offers']['price'] === '80.00', 'Public offer never exposes VIP pricing');
check($meta['graph'][0]['offers']['availability'] === 'https://schema.org/OutOfStock', 'Sold-out products remain published but unavailable');
check(!isset($meta['graph'][0]['aggregateRating']) && !isset($meta['graph'][0]['brand']), 'Visits are not reviews and no brand is invented');
unset($product['precio_publico']);
check(!isset(SupernovaPageMetadata::build('/producto/42', $home, $product)['graph'][0]['offers']), 'Old VIP responses cannot publish a VIP price');
$product['es_vip'] = false;
check(SupernovaPageMetadata::build('/producto/42', $home, $product)['graph'][0]['offers']['price'] === '57.50', 'Guest API response provides public price');
$html = file_get_contents(__DIR__ . '/../../dist/spa/index.html');
$output = SupernovaPageMetadata::replaceHead($html, $meta);
check(substr_count($output, '<title>') === 1 && substr_count($output, 'id="store-structured-data"') === 1, 'Default metadata is replaced, not duplicated');
check(strpos($output, 'Tienda de Uñas, Pestañas, Geles y Más</title>') === false, 'Generic title removed from product HTML');
check(strpos($output, '/assets/index-') !== false && strpos($output, 'G-9BG23LE340') !== false, 'SPA assets and Analytics retained');
$product['nombre'] = 'Gel "rosa" </script><script>alert(1)</script>';
$product['descripcion'] = 'Texto con "comillas" <img src=x onerror=alert(1)>';
$unsafe = SupernovaPageMetadata::replaceHead($html, SupernovaPageMetadata::build('/producto/42', $home, $product));
check(strpos($unsafe, '<script>alert(1)</script>') === false && strpos($unsafe, '<img src=x') === false, 'Product text cannot inject executable head markup');
check(SupernovaPageMetadata::build('/producto/99', $home, null, 404)['robots'] === 'noindex, follow', 'Missing products not indexed');
check(SupernovaPageMetadata::build('/producto/42', $home, null, 503)['graph'] === array(), 'API failure invents no product data');
foreach (array('/carrito', '/checkout', '/panel', '/pedido/token/pdf') as $path) check(SupernovaPageMetadata::build($path, $home)['robots'] === 'noindex, follow', 'Private workflow page not indexed');
$homepage = SupernovaPageMetadata::build('/', $home);
check(count($homepage['graph']) === 2 && count($homepage['graph'][1]['sameAs']) === 4, 'Homepage contains website and current organization identities');
if (isset($argv[1]) && $argv[1] === '--json') echo json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
else echo "Server metadata checks passed: initial HTML, product URLs, public prices, stock, privacy, safe JSON and no invented ratings.\n";
