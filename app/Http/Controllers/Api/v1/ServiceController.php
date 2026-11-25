<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Service\CreateServiceRequest;
use App\Http\Requests\Service\IndexMyServicesRequest;
use App\Http\Requests\Service\IndexServicesRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Services\v1\ServiceService;

class ServiceController extends ApiController
{
    private ServiceService $service;

    public function __construct()
    {
        $this->service = new ServiceService();
    }

    public function index(IndexServicesRequest $request)
    {
        $params = $request->validated();
        // Флаги ставим здесь, а не в сервисе: тот же index обслуживает
        // /services/my, где свои услуги скрывать не нужно.
        $params['exclude_deleted_users'] = true;
        $params['only_active'] = true;

        return $this->result($this->service->index($params));
    }

    public function indexMy(IndexMyServicesRequest $request)
    {
        $params = $request->validated();
        $params['user_id'] = $this->authUser()->id;

        return $this->result($this->service->index($params));
    }

    public function info($id)
    {
        return $this->result($this->service->info($id));
    }

    public function create(CreateServiceRequest $request)
    {
        return $this->result($this->service->create($request->validated()));
    }

    public function update($id, UpdateServiceRequest $request)
    {
        return $this->result($this->service->update($id, $request->validated()));
    }

    public function delete($id)
    {
        return $this->result($this->service->delete($id));
    }
}
