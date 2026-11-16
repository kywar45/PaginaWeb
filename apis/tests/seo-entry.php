<?php
// Run with php -n: replace cURL with a deterministic public API fixture, never a live request.
if (extension_loaded('curl')) throw new RuntimeException('Run this test with php -n');
foreach (array('CURLOPT_RETURNTRANSFER', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT', 'CURLOPT_FOLLOWLOCATION', 'CURLOPT_HTTPHEADER', 'CURLINFO_HTTP_CODE') as $i => $name) define($name, $i + 1);
$requestedUrl = null;
function curl_init($url) { $GLOBALS['requestedUrl'] = $url; return true; }
function curl_setopt_array($handle, $options) {
    if ($options[CURLOPT_HTTPHEADER] !== array('Accept: application/json')) throw new RuntimeException('Metadata must not forward private credentials');
    return true;
}
function curl_exec($handle) {
    return json_encode(array('success' => true, 'data' => array('producto' => array('id_producto' => 42,
        'nombre' => 'Gel Prueba', 'descripcion' => 'Descripción del producto', 'precio_final' => 80,
        'es_vip' => false, 'estatus' => 1, 'imagen' => 'https://www.importadorasupernova.com/imagenes/42/foto.png'))));
}
function curl_getinfo($handle, $option) { return 200; }
function curl_close($handle) {}
$_SERVER['REQUEST_URI'] = '/ImportadoraSupernova/producto/42/gel-prueba?tracking=1';
$_COOKIE['sn_session'] = 'private-token-not-for-metadata';
ob_start();
include __DIR__ . '/../../dist/spa/index.php';
$html = ob_get_clean();
if (http_response_code() !== 200 || strpos($html, '<title>Gel Prueba | Importadora Supernova</title>') === false
    || strpos($html, 'name="store-base-url"') === false && strpos($html, 'name=store-base-url') === false
    || strpos($html, '"price":"80.00"') === false || strpos($html, 'private-token') !== false
    || $requestedUrl !== 'https://www.importadorasupernova.com/StoreApi/v1/productos?id_producto=42') {
    throw new RuntimeException('Initial product HTML does not match its public API data');
}
echo "PHP entrypoint checks passed: initial product metadata, public-only request and compiled SPA preserved.\n";
