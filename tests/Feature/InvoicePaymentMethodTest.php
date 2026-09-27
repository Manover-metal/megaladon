<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

class InvoicePaymentMethodTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->makeUser('+77001110000');
        $this->makeExecutor($this->user);
    }

    private function createInvoice(array $body)
    {
        $token = $this->user->createToken('api')->plainTextToken;

        return $this->withToken($token)->postJson('/api/invoice/executor/create', $body);
    }

    private function disableManual(string $platform): void
    {
        Setting::where('key', 'manual_payment_' . $platform)->update(['value' => '0']);
    }

    public function test_old_app_without_platform_ignores_flag(): void
    {
        $this->disableManual('ios');
        $this->disableManual('android');
        $plan = $this->makePlan(Subscription::EXECUTOR);

        $response = $this->createInvoice(['subscription_id' => $plan->id])->assertOk();

        $invoice = Invoice::sole();
        $this->assertSame(Invoice::METHOD_MANUAL, $invoice->payment_method);
        $this->assertSame(Invoice::STATUS_CREATED, $invoice->status);
        // Поля ответа лежат в data — форма BaseService::result.
        $response->assertJsonPath('data.uuid', $invoice->uuid);
    }

    public function test_manual_rejected_when_disabled_for_platform(): void
    {
        $this->disableManual('ios');
        $plan = $this->makePlan(Subscription::EXECUTOR);

        $this->createInvoice([
            'subscription_id' => $plan->id,
            'payment_method' => 'manual',
            'platform' => 'ios',
        ])->assertStatus(422);

        $this->assertSame(0, Invoice::count());
    }

    public function test_manual_allowed_on_other_platform(): void
    {
        $this->disableManual('ios');
        $plan = $this->makePlan(Subscription::EXECUTOR);

        $this->createInvoice([
            'subscription_id' => $plan->id,
            'payment_method' => 'manual',
            'platform' => 'android',
        ])->assertOk();
    }

    public function test_free_plan_ignores_manual_flag(): void
    {
        $this->disableManual('ios');
        $plan = $this->makePlan(Subscription::EXECUTOR, 0);

        $this->createInvoice([
            'subscription_id' => $plan->id,
            'payment_method' => 'apple',
            'platform' => 'ios',
        ])->assertOk();

        $invoice = Invoice::sole();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(Invoice::METHOD_MANUAL, $invoice->payment_method);
    }

    public function test_apple_requires_product_id(): void
    {
        $plan = $this->makePlan(Subscription::EXECUTOR);

        $this->createInvoice([
            'subscription_id' => $plan->id,
            'payment_method' => 'apple',
            'platform' => 'ios',
        ])->assertStatus(422);
    }

    public function test_apple_invoice_is_created_as_request(): void
    {
        $plan = $this->makePlan(Subscription::EXECUTOR, 5000, ['apple_product_id' => 'executor_1m']);

        $this->createInvoice([
            'subscription_id' => $plan->id,
            'payment_method' => 'apple',
            'platform' => 'ios',
        ])->assertOk();

        $invoice = Invoice::sole();
        $this->assertSame(Invoice::METHOD_APPLE, $invoice->payment_method);
        $this->assertSame(Invoice::STATUS_CREATED, $invoice->status);
    }

    public function test_store_invoice_accepts_payment_method(): void
    {
        $this->makeStore($this->user);
        $plan = $this->makePlan(Subscription::STORE, 5000, ['google_product_id' => 'store_1m']);
        $token = $this->user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/invoice/store/create', [
            'subscription_id' => $plan->id,
            'payment_method' => 'google',
            'platform' => 'android',
        ])->assertOk()->assertJsonStructure(['data' => ['uuid']]);

        $this->assertSame(Invoice::METHOD_GOOGLE, Invoice::sole()->payment_method);
    }

    public function test_payment_methods_endpoint_reports_flag_per_platform(): void
    {
        $this->disableManual('ios');

        $this->getJson('/api/payment-methods?platform=ios')->assertOk()->assertJsonPath('manual', false);
        $this->getJson('/api/payment-methods?platform=android')->assertOk()->assertJsonPath('manual', true);
        $this->getJson('/api/payment-methods?platform=web')->assertStatus(422);
    }
}
