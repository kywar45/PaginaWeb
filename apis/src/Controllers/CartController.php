<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\CartService;

class CartController
{
    private $service;

    public function __construct(CartService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            return Response::success($this->service->execute(), 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        }
    }

    public function update()
    {
        try {
            // JSON evita que formularios de otros sitios modifiquen el carrito mediante cookies.
            $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
            if ($type !== 'application/json') {
                throw new ApiException('VALIDATION_ERROR', 'Se requiere contenido JSON.', 415);
            }
            $data = Request::json();
            return Response::success($this->service->execute(
                $data['accion'] ?? '', $data['id_producto'] ?? null, $data['cantidad'] ?? null
            ), 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        }
    }
}
