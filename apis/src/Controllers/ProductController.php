<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Services\ProductService;

class ProductController
{
    private const COLLECTIONS = array('ofertas', 'nuevos', 'vistos', 'recientes', 'mas_vendidos');

    private $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            if (array_key_exists('id_producto', $_GET)) {
                $id = $this->integerQuery('id_producto', null, 1, 2147483647);
                return Response::success($this->service->getProduct($id), 200);
            }
            $collection = $this->collectionQuery('coleccion', 'recientes');
            $page = $this->integerQuery('page', 1, 1, 1000000);
            $subcategoryId = $this->integerQuery('id_subcategoria', null, 1, 2147483647);
            $categoryId = $this->integerQuery('id_categoria', null, 1, 2147483647);
            $brandId = $this->integerQuery('id_marca', null, 1, 2147483647);
            $onlyAvailable = $this->integerQuery('solo_disponibles', 0, 0, 1) === 1;
            $limit = $this->integerQuery('limit', ($subcategoryId === null && $categoryId === null) ? 20 : 30, 1, 100);

            if (array_key_exists('q', $_GET)) {
                $query = $_GET['q'];
                if (!is_string($query) || !preg_match('//u', $query)) {
                    throw new ApiException('VALIDATION_ERROR', 'Busqueda invalida.', 422);
                }
                $query = trim(preg_replace('/\s+/u', ' ', $query));
                if ($query === '' || preg_match_all('/./u', $query) > 80) {
                    throw new ApiException('VALIDATION_ERROR', 'La busqueda debe tener entre 1 y 80 caracteres.', 422);
                }
                $sort = isset($_GET['orden']) ? $_GET['orden'] : 'relevancia';
                if (!is_string($sort) || !in_array($sort, array('relevancia', 'precio_asc', 'precio_desc', 'recientes'), true)) {
                    throw new ApiException('VALIDATION_ERROR', 'Orden de busqueda invalido.', 422);
                }
                $offersOnly = $this->integerQuery('solo_ofertas', 0, 0, 1) === 1;
                return Response::success($this->service->searchProducts($query, $page, $limit, $sort, $onlyAvailable, $offersOnly), 200);
            }

            if ($brandId !== null) {
                return Response::success($this->service->listBrandProducts($brandId, $page, $limit, $onlyAvailable), 200);
            }
            return Response::success($this->service->listProducts($collection, $page, $limit, $subcategoryId, $onlyAvailable, $categoryId), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    public function randomSubcategories()
    {
        try {
            $subcategoryLimit = $this->integerQuery('subcategory_limit', 3, 1, 6);
            $productLimit = $this->integerQuery('product_limit', 6, 1, 12);

            return Response::success(
                $this->service->listRandomSubcategoryGroups($subcategoryLimit, $productLimit),
                200
            );
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    private function collectionQuery($name, $default)
    {
        if (!array_key_exists($name, $_GET)) {
            return $default;
        }

        $value = $_GET[$name];
        if (!is_string($value) || !in_array($value, self::COLLECTIONS, true)) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro invalido: ' . $name . '.', 422);
        }

        return $value;
    }

    private function integerQuery($name, $default, $min, $max)
    {
        if (!array_key_exists($name, $_GET)) {
            return $default;
        }

        $value = $_GET[$name];
        if (!is_string($value) || !preg_match('/^[0-9]+$/D', $value)) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro invalido: ' . $name . '.', 422);
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => $min, 'max_range' => $max),
        ));

        if ($integer === false) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro fuera de rango: ' . $name . '.', 422);
        }

        return $integer;
    }
}
