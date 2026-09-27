<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\PaidInvoiceRequest;
use App\Services\v1\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends ApiController
{
    protected InvoiceService $service;

    public function __construct() {
        $this->service = new InvoiceService();
    }

    public function executorCreate(CreateInvoiceRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->service->executorCreate($this->authUser(), $data));
    }

    public function storeCreate(CreateInvoiceRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->service->storeCreate($this->authUser(), $data));
    }

    public function paymentMethods(Request $request)
    {
        $data = $request->validate(['platform' => ['required', 'in:ios,android']]);
        return $this->result($this->service->paymentMethods($data['platform']));
    }

    public function paid($id, PaidInvoiceRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->service->paid($id, $data));
    }
}
