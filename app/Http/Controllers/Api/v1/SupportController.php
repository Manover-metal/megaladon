<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Support\CreateTicketRequest;
use App\Services\v1\SupportService;

class SupportController extends ApiController
{
    private SupportService $service;

    public function __construct()
    {
        $this->service = new SupportService();
    }

    public function index()
    {
        return $this->result($this->service->my($this->authUser()));
    }

    public function store(CreateTicketRequest $request)
    {
        return $this->result($this->service->create($this->authUser(), $request->validated()));
    }
}
