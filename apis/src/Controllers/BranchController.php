<?php

namespace App\Controllers;

use App\Http\Response;
use App\Services\BranchService;

class BranchController
{
    private $service;

    public function __construct(BranchService $service) { $this->service = $service; }

    public function index() { return Response::success($this->service->listBranches(), 200); }
}
