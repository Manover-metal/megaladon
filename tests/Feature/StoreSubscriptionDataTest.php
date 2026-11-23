<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

class StoreSubscriptionDataTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    public function test_invoice_gets_uuid_and_manual_method_on_create(): void
    {
        $executor = $this->makeExecutor($this->makeUser('+77001110000'));
        $plan = $this->makePlan(Subscription::EXECUTOR);

        $invoice = $executor->invoices()->create(['subscription_id' => $plan->id]);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $invoice->uuid);
        $this->assertSame(Invoice::METHOD_MANUAL, $invoice->payment_method);
        $this->assertSame($invoice->uuid, $invoice->fresh()->uuid);
    }

    // Пока подписки скрыты, ручная оплата выключена (миграция флага подписок):
    // старые версии приложения не создадут через неё заявок.
    public function test_manual_payment_flags_are_off_after_migration(): void
    {
        $this->assertFalse(Setting::flag('manual_payment_ios'));
        $this->assertFalse(Setting::flag('manual_payment_android'));
    }

    public function test_flag_reads_one_as_true_zero_and_missing_as_false(): void
    {
        Setting::where('key', 'manual_payment_ios')->update(['value' => '1']);

        $this->assertTrue(Setting::flag('manual_payment_ios'));
        $this->assertFalse(Setting::flag('manual_payment_android'));
        $this->assertFalse(Setting::flag('no_such_key'));
    }

    public function test_subscriptions_endpoint_returns_product_ids(): void
    {
        $this->makePlan(Subscription::EXECUTOR, 5000, [
            'apple_product_id' => 'executor_1m',
            'google_product_id' => 'executor_1m_android',
        ]);

        $this->getJson('/api/subscriptions?type=executor')
            ->assertOk()
            ->assertJsonPath('list.0.apple_product_id', 'executor_1m')
            ->assertJsonPath('list.0.google_product_id', 'executor_1m_android');
    }

    public function test_subscriptions_endpoint_returns_trial_months(): void
    {
        $this->makePlan(Subscription::EXECUTOR, 5000, ['trial_months' => 3]);
        $this->makePlan(Subscription::STORE, 9000);

        $this->getJson('/api/subscriptions?type=executor')->assertJsonPath('list.0.trial_months', 3);
        $this->getJson('/api/subscriptions?type=store')->assertJsonPath('list.0.trial_months', null);
    }
}
