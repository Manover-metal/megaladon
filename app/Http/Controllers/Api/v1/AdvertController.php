<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Advert\CreateAdvertRequest;
use App\Http\Requests\Advert\IndexAdvertsRequest;
use App\Http\Requests\Advert\IndexMyAdvertsRequest;
use App\Http\Requests\Advert\UpdateAdvertRequest;
use App\Services\v1\AdvertService;
use Illuminate\Http\Request;

class AdvertController extends ApiController
{
    private AdvertService $advertService;

    public function __construct()
    {
        $this->advertService = new AdvertService();
    }

    public function index(IndexAdvertsRequest $request)
    {
        $params = $request->validated();
        // Флаг ставим здесь, а не в AdvertService::index: тот же метод сервиса
        // обслуживает и /adverts/my, где свои объявления скрывать не нужно.
        $params['exclude_deleted_users'] = true;
        $params['only_active'] = true;

        return $this->result($this->advertService->index($params));
    }

    public function indexMy(IndexMyAdvertsRequest $request)
    {
        $params = $request->validated();
        $params['user_id'] = $this->authUser()->id;

        return $this->result($this->advertService->index($params));
    }

    public function info($id)
    {
        return $this->result($this->advertService->info($id));
    }

    public function create(CreateAdvertRequest $request)
    {
        return $this->result($this->advertService->create($request->validated()));
    }

    public function update($id, UpdateAdvertRequest $request)
    {
        return $this->result($this->advertService->update($id, $request->validated()));
    }

    public function delete($id)
    {
        return $this->result($this->advertService->delete($id));
    }
}
