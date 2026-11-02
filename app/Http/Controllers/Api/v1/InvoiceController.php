<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\PaidInvoiceRequest;
use App\Models\Invoice;
use App\Services\Billing\AppleJws;
use App\Services\v1\InvoiceService;
use App\Services\v1\StorePaymentService;
use Carbon\Carbon;
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

    /**
     * Транзакция App Store от самого приложения: подписка включается сразу, не
     * дожидаясь вебхука. Подпись проверяем по той же цепочке Apple; вебхук с той
     * же транзакцией потом отсеется по store_transaction_id.
     */
    public function appleVerify(Request $request, AppleJws $jws, StorePaymentService $payments)
    {
        $data = $request->validate(['signed_transaction' => ['required', 'string']]);
        $transaction = $jws->decode($data['signed_transaction']);

        if (($transaction['bundleId'] ?? null) !== config('billing.apple.bundle_id')
            || empty($transaction['appAccountToken'])
            || empty($transaction['expiresDate'])) {
            return response()->json(['success' => false, 'message' => 'invalid transaction'], 422);
        }

        $invoice = Invoice::where('uuid', $transaction['appAccountToken'])->first();
        if (!$invoice
            || (int) optional($invoice->invoiceable)->user_id !== (int) $this->authUser()->id
            || optional($invoice->subscription)->apple_product_id !== ($transaction['productId'] ?? null)) {
            return response()->json(['success' => false, 'message' => 'invoice mismatch'], 403);
        }

        $payments->paid(
            $transaction['appAccountToken'],
            (string) $transaction['transactionId'],
            Carbon::createFromTimestampMs($transaction['expiresDate']),
            $transaction,
        );

        return response()->json(['success' => true]);
    }

    public function paid($id, PaidInvoiceRequest $request)
    {
        $data = $request->validated();
        return $this->result($this->service->paid($id, $data));
    }
}
