<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Response;
use App\Services\BannerService;

class BannerController
{
    private $service;

    public function __construct(BannerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $page = $this->integerQuery('page', 1, 1, 1000000);
            $limit = $this->integerQuery('limit', 50, 1, 100);
            $tipo = $this->integerQuery('tipo', null, -2147483648, 2147483647);

            return Response::success($this->service->listBanners($tipo, $page, $limit), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    private function integerQuery($name, $default, $min, $max)
    {
        if (!array_key_exists($name, $_GET)) {
            return $default;
        }

        $value = $_GET[$name];
        if (!is_string($value) || !preg_match('/^-?[0-9]+$/D', $value)) {
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
