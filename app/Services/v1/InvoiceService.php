<?php

namespace App\Services\v1;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\InvoiceRepo;
use App\Services\BaseService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use SimpleXMLElement;

class InvoiceService extends BaseService
{
    private array $config;

    public function __construct() {
        $this->config = config('paybox');
    }

    public function executorCreate(User $user, array $data)
    {
        $executor = $user->executor;
        if (!$executor) {
            return $this->errNotFound(__('invoice.executor_not_found'));
        }

        $subscription = Subscription::find($data['subscription_id']);
        if (!$subscription) {
            return $this->errNotFound(__('invoice.subscription_not_found'));
        }
        if ($subscription->type !== Subscription::EXECUTOR) {
            return $this->errValidate(__('invoice.subscription_not_for_executor'));
        }

        if ($error = $this->paymentMethodError($subscription, $data)) {
            return $error;
        }
        unset($data['platform']);
        // Явный null из запроса перетёр бы дефолт модели, колонка NOT NULL.
        $data['payment_method'] = $data['payment_method'] ?? Invoice::METHOD_MANUAL;

        if ($subscription->price == 0) {
            $data['status'] = Invoice::STATUS_PAID;
            $data['expired_at'] = Carbon::now()->addMonths($subscription->validity);
            // Бесплатный тариф в магазин не ходит.
            $data['payment_method'] = Invoice::METHOD_MANUAL;
        }

        $invoice = $executor->invoices()->create($data);

        return $this->result([
            'data' => [
                'invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'phone' => $user->phone,
                'user_id' => $user->id,
            ]
        ]);
    }

    public function storeCreate(User $user, array $data)
    {
        $store = $user->store;
        if (!$store) {
            return $this->errNotFound(__('invoice.store_not_found'));
        }

        $subscription = Subscription::find($data['subscription_id']);
        if (!$subscription) {
            return $this->errNotFound(__('invoice.subscription_not_found'));
        }
        if ($subscription->type !== Subscription::STORE) {
            return $this->errValidate(__('invoice.subscription_not_for_store'));
        }

        if ($error = $this->paymentMethodError($subscription, $data)) {
            return $error;
        }
        unset($data['platform']);
        // Явный null из запроса перетёр бы дефолт модели, колонка NOT NULL.
        $data['payment_method'] = $data['payment_method'] ?? Invoice::METHOD_MANUAL;

        if ($subscription->price == 0) {
            $data['status'] = Invoice::STATUS_PAID;
            $data['expired_at'] = Carbon::now()->addMonths($subscription->validity);
            // Бесплатный тариф в магазин не ходит.
            $data['payment_method'] = Invoice::METHOD_MANUAL;
        }

        $invoice = $store->invoices()->create($data);

        return $this->result([
            'data' => [
                'invoice_id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'phone' => $user->phone,
                'user_id' => $user->id,
            ]
        ]);
    }

    public function paid(int $invoiceId, array $data)
    {

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return $this->errNotFound(__('invoice.invoice_not_found'));
        }

        $check = $this->checkTransaction($data['transaction_id']);
        if (!$this->isSuccess($check)) {
            return $check;
        }

        if ($invoice !== Invoice::STATUS_PAID) {
            if ($invoice->invoiceable->activeInvoice()) {
                (new InvoiceRepo())->update($invoice->id, [
                    'meta' => json_encode($data), 
                    'status' => Invoice::STATUS_PAID,
                    'expired_at' => Carbon::parse($invoice->invoiceable->activeInvoice()->expired_at)->addMonths($invoice->subscription->validity),
                ]);
            }
            else {
                (new InvoiceRepo())->update($invoice->id, [
                    'meta' => json_encode($data), 
                    'status' => Invoice::STATUS_PAID,
                    'expired_at' => Carbon::now()->addMonths($invoice->subscription->validity),
                ]);
            }
        }

        return $this->ok();
    }

    public function paymentMethods(string $platform): array
    {
        return $this->result(['manual' => Setting::flag('manual_payment_' . $platform)]);
    }

    /**
     * Ошибка 422, если выбранный способ оплаты для тарифа недоступен, иначе null.
     * Бесплатный тариф активируется сразу — для него способ не важен.
     */
    private function paymentMethodError(Subscription $subscription, array $data): ?array
    {
        if ($subscription->price == 0) {
            return null;
        }

        $method = $data['payment_method'] ?? Invoice::METHOD_MANUAL;

        if ($method === Invoice::METHOD_APPLE && empty($subscription->apple_product_id)) {
            return $this->errValidate(__('invoice.store_product_missing'));
        }
        if ($method === Invoice::METHOD_GOOGLE && empty($subscription->google_product_id)) {
            return $this->errValidate(__('invoice.store_product_missing'));
        }
        // platform не присылают старые версии приложения — у них флаг не проверяем,
        // иначе у них сломается покупка.
        if ($method === Invoice::METHOD_MANUAL && !empty($data['platform'])
            && !Setting::flag('manual_payment_' . $data['platform'])) {
            return $this->errValidate(__('invoice.manual_payment_disabled'));
        }

        return null;
    }

    private function checkTransaction($transaction_id)
    {
        $request = new Request('POST', 'https://api.paybox.money/get_status2.php');

        $body = json_encode($this->generatePaymentParams($transaction_id));

        $client = new Client();
        $response = null;
        $response =  $client->send($request, [
            'body' => $body,
            'headers' => ['Content-Type' => 'application/json'],
            'connect_timeout' => 10,
            'verify' => false,
            'http_errors' => false,
        ]);

        $responseData = new SimpleXMLElement($response->getBody()->getContents());
        if ($responseData->pg_status == 'error') {
            return $this->error(500, __('invoice.transaction_check_failed', ['code' => $responseData->pg_error_code, 'description' => $responseData->pg_error_description]));
        }
        if ($responseData->pg_transaction_status == 'ok') {
            return $this->ok();
        }

        return $this->errNotAcceptable(__('invoice.transaction_not_paid'));
    }

    private function generatePaymentParams($transaction_id)
    {
        $request = [
            'pg_merchant_id'=> $this->config['merchant_id'],
            'pg_payment_id' => $transaction_id,
            'pg_salt' => Str::random(16),
        ];

        //generate a signature and add it to the array
        ksort($request); //sort alphabetically
        array_unshift($request, 'get_status2.php');
        array_push($request, $this->config['secret_key']); //add your secret key (you can take it in your personal cabinet on paybox system)

        $request['pg_sig'] = md5(implode(';', $request)); // signature

        unset($request[0], $request[1]);

        return $request;
    }
}