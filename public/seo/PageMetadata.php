<?php

class SupernovaPageMetadata
{
    const NAME = 'Importadora Supernova';
    const IMAGE = 'https://www.importadorasupernova.com/Logo/logo_meta.jpg';
    const DESCRIPTION = 'Productos para uñas, pestañas, geles, belleza y hogar. Compra en Importadora Supernova con envío nacional o entrega en nuestras tiendas.';

    public static function fetchProduct($id)
    {
        if (!function_exists('curl_init')) { error_log('Supernova metadata: PHP cURL is unavailable.'); return array('status' => 503); }
        // Fixed public endpoint: never forward session cookies, credentials or a caller-provided URL.
        $curl = curl_init('https://www.importadorasupernova.com/StoreApi/v1/productos?id_producto=' . (int) $id);
        curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => array('Accept: application/json')));
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $payload = is_string($body) ? json_decode($body, true) : null;
        if ($status === 404) return array('status' => 404);
        $product = $payload['data']['producto'] ?? null;
        if ($status !== 200 || empty($payload['success']) || !is_array($product)
            || (int) ($product['id_producto'] ?? $product['id'] ?? 0) !== $id || !empty($product['es_vip'])) {
            error_log('Supernova metadata: public product lookup failed (HTTP ' . $status . ').');
            return array('status' => 503);
        }
        return array('status' => 200, 'product' => $product);
    }

    private static function text($value, $max = 160)
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
        preg_match_all('/./us', $value, $characters);
        return implode('', array_slice($characters[0], 0, $max));
    }
    private static function image($value, $home)
    {
        if (!is_string($value) || trim($value) === '' || strpos($value, 'product-no-image.svg') !== false) return null;
        if (strpos($value, '//') === 0) $value = 'https:' . $value;
        if (strpos($value, '/') === 0) $value = 'https://www.importadorasupernova.com' . $value;
        elseif (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $value)) $value = $home . $value;
        return filter_var($value, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $value) ? $value : null;
    }
    private static function slug($value)
    {
        // Match the storefront's NFD slug rules without transliterating symbols (™ -> TM).
        $value = strtr((string) $value, array(
            'á'=>'a','à'=>'a','â'=>'a','ä'=>'a','ã'=>'a','å'=>'a','Á'=>'a','À'=>'a','Â'=>'a','Ä'=>'a','Ã'=>'a','Å'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
            'ó'=>'o','ò'=>'o','ô'=>'o','ö'=>'o','õ'=>'o','Ó'=>'o','Ò'=>'o','Ô'=>'o','Ö'=>'o','Õ'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','ñ'=>'n','Ñ'=>'n','ç'=>'c','Ç'=>'c','ý'=>'y','ÿ'=>'y','Ý'=>'y',
        ));
        $value = preg_replace('/[\x{0300}-\x{036f}]/u', '', $value);
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-') ?: 'producto';
    }

    public static function build($path, $home, $product = null, $status = 200)
    {
        $home = rtrim($home, '/') . '/';
        $titles = array('/ofertas' => 'Ofertas', '/mas-vendidos' => 'Más vendidos', '/nuevos' => 'Nuevos productos',
            '/sucursales' => 'Sucursales', '/marcas' => 'Nuestras marcas',
            '/buscar' => 'Buscar productos', '/carrito' => 'Mi carrito', '/checkout' => 'Checkout', '/pedido/completado' => 'Pedido completado',
            '/login' => 'Iniciar sesión', '/register' => 'Crear cuenta', '/panel' => 'Mi cuenta');
        $private = preg_match('~^/(carrito|checkout|login|register|panel|buscar|pedido)(/|$)~', $path);
        $meta = array('title' => $path === '/' ? self::NAME . ' - Tienda de Uñas, Pestañas, Geles y Más' : ($titles[$path] ?? 'Catálogo') . ' | ' . self::NAME,
            'description' => self::DESCRIPTION, 'url' => $home . ltrim($path, '/'), 'image' => self::IMAGE,
            'type' => 'website', 'robots' => $private || $status !== 200 ? 'noindex, follow' : 'index, follow', 'graph' => array());
        if ($path === '/') {
            $meta['graph'] = array(
                array('@type' => 'WebSite', '@id' => $home . '#website', 'name' => self::NAME, 'url' => $home),
                array('@type' => 'Organization', '@id' => $home . '#organization', 'name' => self::NAME, 'url' => $home,
                    'logo' => self::IMAGE, 'sameAs' => array('https://www.facebook.com/profile.php?id=61558081776009',
                        'https://www.instagram.com/lemussaoficial/', 'https://www.tiktok.com/@supernovashopmx', 'https://www.youtube.com/@lemussaoficial3601')),
            );
        }
        if ($product !== null) {
            $meta['url'] = $home . 'producto/' . (int) ($product['id_producto'] ?? $product['id']) . '/' . self::slug($product['nombre']);
            $meta['title'] = self::text($product['nombre'], 250) . ' | ' . self::NAME;
            $meta['description'] = self::text($product['descripcion'] ?? '') ?: self::text($product['nombre'], 100) . '. Consulta precio y disponibilidad en ' . self::NAME . '.';
            $images = array();
            foreach (array_merge(array($product['imagen'] ?? null), $product['imagenes'] ?? array()) as $value) {
                $image = self::image($value, $home);
                if ($image !== null && !in_array($image, $images, true)) $images[] = $image;
            }
            $meta['image'] = $images ? $images[0] : self::IMAGE;
            $meta['type'] = 'product';
            $schema = array('@type' => 'Product', 'name' => self::text($product['nombre'], 500), 'description' => $meta['description'], 'url' => $meta['url']);
            if (!empty($product['codigo'])) $schema['sku'] = (string) $product['codigo'];
            if ($images) $schema['image'] = $images;
            $price = $product['precio_publico'] ?? (!empty($product['es_vip']) ? null : ($product['precio_final'] ?? null));
            if (is_numeric($price) && is_finite((float) $price) && (float) $price > 0) {
                $schema['offers'] = array('@type' => 'Offer', 'url' => $meta['url'], 'priceCurrency' => 'MXN', 'price' => number_format((float) $price, 2, '.', ''),
                    'availability' => (int) ($product['estatus'] ?? 0) === 1 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'seller' => array('@type' => 'Organization', 'name' => self::NAME));
            }
            $meta['graph'] = array($schema);
        } elseif ($status === 404) {
            $meta['title'] = 'Producto no disponible | ' . self::NAME;
            $meta['description'] = 'El producto solicitado no existe o no está publicado.';
        } elseif (strpos($path, '/producto/') === 0) {
            $meta['title'] = 'Producto | ' . self::NAME;
        }
        return $meta;
    }

    public static function replaceHead($html, array $meta)
    {
        $escape = function ($value) { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
        $tags = '<title>' . $escape($meta['title']) . '</title>';
        foreach (array('description' => $meta['description'], 'robots' => $meta['robots']) as $name => $value) $tags .= '<meta name="' . $name . '" content="' . $escape($value) . '">';
        foreach (array('title' => $meta['title'], 'description' => $meta['description'], 'url' => $meta['url'], 'image' => $meta['image'],
            'type' => $meta['type'], 'site_name' => self::NAME, 'locale' => 'es_MX') as $name => $value) $tags .= '<meta property="og:' . $name . '" content="' . $escape($value) . '">';
        foreach (array('card' => 'summary_large_image', 'title' => $meta['title'], 'description' => $meta['description'], 'image' => $meta['image']) as $name => $value) $tags .= '<meta name="twitter:' . $name . '" content="' . $escape($value) . '">';
        $tags .= '<link rel="canonical" href="' . $escape($meta['url']) . '">';
        if ($meta['graph']) $tags .= '<script id="store-structured-data" type="application/ld+json">' . json_encode(array('@context' => 'https://schema.org', '@graph' => $meta['graph']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
        return preg_replace_callback('~<head\b[^>]*>(.*?)</head>~is', function ($match) use ($tags) {
            $head = preg_replace('~<title\b[^>]*>.*?</title>~is', '', $match[1]);
            $head = preg_replace('~<meta\b(?=[^>]*(?:name|property)=["\']?(?:description|robots|og:[a-z_]+|twitter:[a-z_]+)(?:["\'\s>]))[^>]*>~i', '', $head);
            $head = preg_replace('~<link\b(?=[^>]*rel=["\']?canonical(?:["\'\s>]))[^>]*>~i', '', $head);
            $head = preg_replace('~<script\b(?=[^>]*id=["\']?store-structured-data(?:["\'\s>]))[^>]*>.*?</script>~is', '', $head);
            return '<head>' . $tags . $head . '</head>';
        }, $html, 1);
    }
}
