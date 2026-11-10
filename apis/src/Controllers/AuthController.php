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

    public function logout()
    {
        try {
            return Response::success($this->service->logout(), 200);
        } catch (ApiException $e) {
            return Response::error($e->getErrorCodeName(), $e->getMessage(), $e->getStatus());
        }
    }
}
