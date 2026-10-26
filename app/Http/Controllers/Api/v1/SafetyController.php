<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Safety\BlockRequest;
use App\Http\Requests\Safety\ReportRequest;
use App\Services\v1\SafetyService;

class SafetyController extends ApiController
{
    private SafetyService $service;

    public function __construct()
    {
        $this->service = new SafetyService();
    }

    public function blocked()
    {
        return $this->result($this->service->blocked($this->authUser()));
    }

    public function block(BlockRequest $request)
    {
        return $this->result($this->service->block($this->authUser(), (int) $request->validated()['user_id']));
    }

    public function unblock(int $userId)
    {
        return $this->result($this->service->unblock($this->authUser(), $userId));
    }

    public function report(ReportRequest $request)
    {
        return $this->result($this->service->report($this->authUser(), $request->validated()));
    }
}
