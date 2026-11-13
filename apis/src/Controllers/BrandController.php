<?php

namespace App\Controllers;

use App\Http\Response;
use App\Services\BrandService;

class BrandController
{
    private $service;

    public function __construct(BrandService $service) { $this->service = $service; }

    public function index() { return Response::success($this->service->listBrands(), 200); }
}
