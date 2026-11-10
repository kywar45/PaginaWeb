<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Services\ProductService;

class ProductController
{
    private const COLLECTIONS = array('ofertas', 'nuevos', 'vistos', 'recientes');

    private $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $collection = $this->collectionQuery('coleccion', 'recientes');
            $page = $this->integerQuery('page', 1, 1, 1000000);
            $limit = $this->integerQuery('limit', 20, 1, 100);

            return Response::success($this->service->listProducts($collection, $page, $limit), 200);
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
