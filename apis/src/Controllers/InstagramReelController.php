<?php

namespace App\Controllers;

use App\Http\Response;
use App\Services\InstagramReelService;

class InstagramReelController
{
    private $service;

    public function __construct(InstagramReelService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Response::success($this->service->listReels(), 200);
    }
}
