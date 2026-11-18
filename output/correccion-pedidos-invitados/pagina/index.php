<?php
// Serve the same SPA for everyone, with page-specific metadata already in its HTML.
require_once __DIR__ . '/seo/PageMetadata.php';

$html = file_get_contents(__DIR__ . '/index.html');
if ($html === false) { http_response_code(503); exit('Sitio temporalmente no disponible.'); }
$homeUrl = 'https://www.importadorasupernova.com/ImportadoraSupernova/';
if (preg_match('~<meta\s+name=["\']?store-base-url["\']?\s+content=["\']?([^"\'\s>]+)~i', $html, $match)) $homeUrl = $match[1];
$basePath = parse_url($homeUrl, PHP_URL_PATH) ?: '/';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = strpos($requestPath, $basePath) === 0 ? '/' . substr($requestPath, strlen($basePath)) : $requestPath;
if ($path === '/index.php' || $path === '/index.html') $path = '/';
$product = null;
$status = 200;
if (preg_match('~^/producto/([0-9]+)(?:/[^/]*)?/?$~D', $path, $match)) {
    $id = filter_var($match[1], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 2147483647)));
    $result = $id === false ? array('status' => 404) : SupernovaPageMetadata::fetchProduct($id);
    $status = $result['status'];
    $product = $result['product'] ?? null;
} elseif (!preg_match('~^/(?:|ofertas|mas-vendidos|nuevos|categorias|sucursales|buscar|carrito|checkout|login|register|panel|subcategoria|marcas(?:/[0-9]+)?|catalogo/[^/]+(?:/[^/]+)?|pedido/(?:[^/]+|invitado/[0-9]+/[^/]+)/pdf)/?$~D', $path)) {
    $status = 404;
}
$meta = SupernovaPageMetadata::build($path, $homeUrl, $product, $status);
http_response_code($status);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
echo SupernovaPageMetadata::replaceHead($html, $meta);
