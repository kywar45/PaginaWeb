<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    private const DEFAULT_IMAGE_BASE_URL = 'https://www.importadorasupernova.com/imagenes/';

    private $products;
    private $imageBaseUrl;

    public function __construct(ProductRepository $products)
    {
        $this->products = $products;
        $this->imageBaseUrl = rtrim($this->env('PRODUCT_IMAGE_BASE_URL', self::DEFAULT_IMAGE_BASE_URL), '/') . '/';
    }

    public function listProducts($collection, $page, $limit)
    {
        $rows = $this->products->listPage($collection, $limit + 1, ($page - 1) * $limit);
        $hasMore = count($rows) > $limit;
        $pagination = array(
            'page' => $page,
            'limit' => $limit,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        );
        if ($collection === 'ofertas') {
            $total = $this->products->countOffers();
            $pagination['total'] = $total;
            $pagination['total_pages'] = (int) ceil($total / $limit);
        }
        $items = array();

        foreach (array_slice($rows, 0, $limit) as $row) {
            $items[] = $this->normalizeProduct($row);
        }

        return array(
            'productos' => $items,
            'pagination' => $pagination,
        );
    }

    public function listRandomSubcategoryGroups($subcategoryLimit, $productLimit)
    {
        $groups = array();

        foreach ($this->products->listRandomSubcategoryGroups($subcategoryLimit, $productLimit) as $group) {
            $subcategory = $group['subcategory'];
            $products = array();

            foreach ($group['products'] as $row) {
                $products[] = $this->normalizeProduct($row);
            }

            if (count($products) === 0) {
                continue;
            }

            $groups[] = array(
                'id_categoria' => (int) $subcategory['id_categoria'],
                'nombre_categoria' => (string) $subcategory['nombre_categoria'],
                'id_subcategoria' => (int) $subcategory['id_subcategoria'],
                'nombre_subcategoria' => (string) $subcategory['nombre_subcategoria'],
                'productos' => $products,
            );
        }

        return array('subcategorias' => $groups);
    }

    private function normalizeProduct($row)
    {
        $price = $this->moneyValue($row['preciou']);
        $discountPrice = $this->moneyValue($row['descuento_general']);
        $discountFlag = strtolower(trim((string) $row['descuento']));
        $hasDiscount = $discountFlag === 'si' && $discountPrice > 0;
        $finalPrice = $hasDiscount ? $discountPrice : $price;

        return array(
            'id_producto' => (int) $row['id'],
            'id' => (int) $row['id'],
            'nombre' => (string) $row['nombre'],
            'codigo' => (string) $row['codigo'],
            'descripcion' => (string) $row['descripcion'],
            'imagen' => $this->imageUrl($row['id'], $row['imagen']),
            'imagen_nombre' => $row['imagen'],
            'precio_normal' => $price,
            'precio_final' => $finalPrice,
            'precio_mayoreo' => $this->moneyValue($row['preciom']),
            'precio_caja' => $this->moneyValue($row['precioc']),
            'descuento' => $discountFlag,
            'tiene_descuento' => $hasDiscount,
            'porcentaje_descuento' => $hasDiscount && $price > 0 && $finalPrice < $price
                ? (int) round((($price - $finalPrice) / $price) * 100)
                : 0,
            'ahorro' => $hasDiscount && $finalPrice < $price ? round($price - $finalPrice, 2) : 0,
            'visitas' => (int) $row['visitas'],
            'estatus' => (int) $row['estatus'],
            'minimo' => (int) $row['minimo'],
            'sub_categoria' => $row['sub_categoria'] === null ? null : (int) $row['sub_categoria'],
            'promo' => (int) $row['promo'],
            'barcode' => $row['barcode'],
            'product_nuevo' => $row['product_nuevo'],
        );
    }

    private function moneyValue($value)
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0.0;
        }

        return round((float) $clean, 2);
    }

    private function imageUrl($productId, $filename)
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return null;
        }
        if (preg_match('/^https?:\/\//i', $filename)) {
            return $filename;
        }

        return $this->imageBaseUrl . rawurlencode((string) $productId) . '/' . rawurlencode($filename);
    }

    private function env($name, $default)
    {
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
