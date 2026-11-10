<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Services\CategoryService;

class CategoryController
{
    private $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $categoryStatus = $this->stringQuery('estatus_categoria');
            $subcategoryStatus = $this->stringQuery('estatus_subcategoria');

            return Response::success($this->service->listCategories($categoryStatus, $subcategoryStatus), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    private function stringQuery($name)
    {
        if (!array_key_exists($name, $_GET)) {
            return null;
        }

        $value = $_GET[$name];
        if (!is_string($value)) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro invalido: ' . $name . '.', 422);
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > 255) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro invalido: ' . $name . '.', 422);
        }

        return $value;
    }
}
