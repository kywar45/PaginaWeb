<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Services\AddressService;

class AddressController
{
    private $service;
    public function __construct(AddressService $service) { $this->service = $service; }
    public function index() { return $this->respond(false); }
    public function change() { return $this->respond(true); }
    private function respond($write)
    {
        header('Cache-Control: private, no-store');
        try {
            // Mutations require JSON so cross-site HTML forms cannot submit them.
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            if ($write && !preg_match('/^application\/json(?:\s*;|\s*$)/i', $contentType)) {
                throw new ApiException('UNSUPPORTED_MEDIA_TYPE', 'Envía los datos como JSON.', 415);
            }
            return Response::success($write ? $this->service->change(Request::json()) : $this->service->listing(), 200);
        } catch (ApiException $error) {
            return Response::error($error->getErrorCodeName(), $error->getMessage(), $error->getStatus());
        }
    }
}
