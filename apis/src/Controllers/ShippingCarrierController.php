<?php

namespace App\Controllers;

use App\Http\Response;
use App\Services\ShippingCarrierService;

class ShippingCarrierController
{
    private $service;

    public function __construct(ShippingCarrierService $service) { $this->service = $service; }

    public function index() { return Response::success($this->service->listing(), 200); }
}
