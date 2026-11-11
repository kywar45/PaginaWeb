<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Exceptions\ApiException;

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

    public function getProduct($id)
    {
        $row = $this->products->findProduct($id);
        if (!$row) {
            throw new ApiException('PRODUCT_NOT_FOUND', 'El producto no existe o no esta publicado.', 404);
        }
        $product = $this->normalizeProduct($row);
        $images = array();
        if ($product['imagen'] !== null) $images[] = $product['imagen'];
        foreach ($this->products->productImages($id) as $image) {
            $url = $this->imageUrl($id, $image['a']);
            if ($url !== null && !in_array($url, $images, true)) $images[] = $url;
        }
        $product['imagenes'] = $images;
        $product['minimo_mayoreo'] = (int) $row['topem'];
        $product['minimo_caja'] = (int) $row['topec'];
        $product['categoria'] = $row['id_categoria'] === null ? null : array(
            'id' => (int) $row['id_categoria'], 'name' => (string) $row['nombre_categoria'],
        );
        $product['subcategoria'] = $row['sub_categoria'] === null ? null : array(
            'id' => (int) $row['sub_categoria'], 'name' => (string) $row['nombre_subcategoria'],
        );
        $product['variantes'] = array();
        if (trim((string) $row['codigo']) !== '') {
            foreach ($this->products->productVariants($row['codigo']) as $variant) {
                $product['variantes'][] = $this->normalizeProduct($variant);
            }
        }
        $product['relacionados'] = array();
        if ($product['sub_categoria'] !== null) {
            foreach ($this->products->relatedProducts($product['sub_categoria'], $id, 10) as $related) {
                $product['relacionados'][] = $this->normalizeProduct($related);
            }
        }
        return array('producto' => $product);
    }

    public function listProducts($collection, $page, $limit, $subcategoryId = null, $onlyAvailable = false, $categoryId = null)
    {
        $rows = $this->products->listPage($collection, $limit + 1, ($page - 1) * $limit, $subcategoryId, $onlyAvailable, $categoryId);
        $hasMore = count($rows) > $limit;
        $pagination = array(
            'page' => $page,
            'limit' => $limit,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        );
        if ($categoryId !== null || $subcategoryId !== null || $collection === 'ofertas') {
            if ($categoryId !== null) {
                $total = $this->products->countCategory($collection, $categoryId, $onlyAvailable, $subcategoryId);
            } else {
                $total = $subcategoryId !== null
                    ? $this->products->countSubcategory($collection, $subcategoryId, $onlyAvailable)
                    : $this->products->countOffers($onlyAvailable);
            }
            $pagination['total'] = $total;
            $pagination['total_pages'] = (int) ceil($total / $limit);
            $pagination['has_more'] = $page * $limit < $total;
            $pagination['next_page'] = $pagination['has_more'] ? $page + 1 : null;
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
            'disponible' => (int) $row['estatus'] === 1,
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
