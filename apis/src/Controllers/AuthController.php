<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthController
{
    private $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function login()
    {
        try {
            $data = Request::json();
            $password = isset($data['password']) ? $data['password'] : (isset($data['pass']) ? $data['pass'] : '');
            $remember = !empty($data['remember']);

            $result = $this->service->login(
                isset($data['correo']) ? $data['correo'] : '',
                $password,
                $remember
            );

            return Response::success($result, 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    public function me()
    {
        try {
            return Response::success($this->service->currentUser(), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    public function summary()
    {
        try {
            return Response::success($this->service->accountSummary(), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    public function orders()
    {
        try {
            $page = $this->integerQuery('page', 1, 1000000);
            $limit = $this->integerQuery('limit', 10, 50);
            return Response::success($this->service->accountOrders($page, $limit), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }

    private function integerQuery($name, $default, $max)
    {
        if (!array_key_exists($name, $_GET)) return $default;
        $value = $_GET[$name];
        if (!is_string($value) || !preg_match('/^[0-9]+$/D', $value)) {
            throw new ApiException('VALIDATION_ERROR', 'Parametro invalido: ' . $name . '.', 422);
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => $max)));
        if ($number === false) throw new ApiException('VALIDATION_ERROR', 'Parametro fuera de rango: ' . $name . '.', 422);
        return $number;
    }

    public function logout()
    {
        try {
            return Response::success($this->service->logout(), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }
}
