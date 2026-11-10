<?php

namespace App\Controllers;

use App\Http\Response;
use App\Services\TikTokVideoService;

class TikTokVideoController
{
    private $service;

    public function __construct(TikTokVideoService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Response::success($this->service->listVideos(), 200);
    }
}
