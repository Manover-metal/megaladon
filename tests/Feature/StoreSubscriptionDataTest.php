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

    public function test_manual_payment_flags_are_on_after_migration(): void
    {
        $this->assertTrue(Setting::flag('manual_payment_ios'));
        $this->assertTrue(Setting::flag('manual_payment_android'));
    }

    public function test_flag_reads_zero_as_false_and_missing_as_false(): void
    {
        Setting::where('key', 'manual_payment_ios')->update(['value' => '0']);

        $this->assertFalse(Setting::flag('manual_payment_ios'));
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
}
