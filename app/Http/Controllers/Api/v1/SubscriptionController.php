<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Subscription\IndexSubscriptionRequest;
use App\Services\v1\SubscriptionService;

class SubscriptionController extends ApiController
{
    private SubscriptionService $service;

    public function __construct() {
        $this->service = new SubscriptionService();
    }

    /** Настройки, от которых зависит интерфейс приложения. */
    public function appConfig()
    {
        return response()->json(['subscriptions_enabled' => \App\Models\Subscription::enabled()]);
    }

    public function index(IndexSubscriptionRequest $request)
    {
        $params = $request->validated();
        return $this->result($this->service->index($params));
    }
}
