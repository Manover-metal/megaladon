<?php

namespace Tests\Feature;

use App\Models\Executor;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Notifications\FcmPushNotification;
use App\Services\v1\StorePaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

class StorePaymentServiceTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private Executor $executor;
    private Invoice $request;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Carbon::setTestNow('2026-09-27 12:00:00');

        $this->executor = $this->makeExecutor($this->makeUser('+77001110000'));
        $plan = $this->makePlan(Subscription::EXECUTOR, 5000, ['apple_product_id' => 'executor_1m']);
        $this->request = $this->executor->invoices()->create([
            'subscription_id' => $plan->id,
            'payment_method' => Invoice::METHOD_APPLE,
        ]);
    }

    private function service(): StorePaymentService
    {
        return app(StorePaymentService::class);
    }

    public function test_first_payment_closes_the_request(): void
    {
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), ['k' => 'v']);

        $invoice = $this->request->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame('tx-1', $invoice->store_transaction_id);
        // Дата округлена вверх: 27-го подписка ещё активна.
        $this->assertSame('2026-10-28', Carbon::parse($invoice->expired_at)->toDateString());
        $this->assertNotNull($this->executor->activeInvoice());
    }

    public function test_renewal_creates_new_paid_invoice(): void
    {
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), []);
        $this->service()->paid($this->request->uuid, 'tx-2', Carbon::parse('2026-11-27 14:00:00'), []);

        $this->assertSame(2, Invoice::where('status', Invoice::STATUS_PAID)->count());
        $renewal = Invoice::where('store_transaction_id', 'tx-2')->sole();
        $this->assertSame(Invoice::METHOD_APPLE, $renewal->payment_method);
        // sqlite отдаёт целые строкой — сравниваем по значению.
        $this->assertEquals($this->request->subscription_id, $renewal->subscription_id);
        $this->assertSame('2026-11-28', Carbon::parse($this->executor->activeInvoice()->expired_at)->toDateString());
    }

    public function test_duplicate_transaction_is_ignored(): void
    {
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), []);
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), []);

        $this->assertSame(1, Invoice::count());
    }

    public function test_unknown_uuid_is_ignored(): void
    {
        $this->service()->paid('00000000-0000-4000-8000-000000000000', 'tx-1', Carbon::now()->addMonth(), []);

        $this->assertSame(Invoice::STATUS_CREATED, $this->request->fresh()->status);
    }

    public function test_deleted_owner_is_ignored(): void
    {
        $this->executor->delete();

        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::now()->addMonth(), []);

        $this->assertSame(Invoice::STATUS_CREATED, $this->request->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_refund_cancels_the_invoice(): void
    {
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), []);

        $this->service()->refunded('tx-1');

        $this->assertSame(Invoice::STATUS_CANCELED, $this->request->fresh()->status);
        $this->assertNull($this->executor->activeInvoice());
    }

    public function test_payment_sends_push(): void
    {
        $this->service()->paid($this->request->uuid, 'tx-1', Carbon::parse('2026-10-27 14:00:00'), []);

        Notification::assertSentTo($this->executor->user, FcmPushNotification::class);
    }
}
