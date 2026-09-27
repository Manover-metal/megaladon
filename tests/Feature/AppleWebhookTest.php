<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

/**
 * Подпись JWS пока не проверяется (см. StoreWebhookController::applePayload),
 * поэтому в тестах подпись — заглушка.
 */
class AppleWebhookTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private Invoice $request;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['billing.apple.bundle_id' => 'com.bangertstudio.manover']);

        $executor = $this->makeExecutor($this->makeUser('+77001110000'));
        $plan = $this->makePlan(Subscription::EXECUTOR, 5000, ['apple_product_id' => 'executor_1m']);
        $this->request = $executor->invoices()->create([
            'subscription_id' => $plan->id,
            'payment_method' => Invoice::METHOD_APPLE,
        ]);
    }

    private function jws(array $payload): string
    {
        return JWT::urlsafeB64Encode('{"alg":"ES256"}') . '.'
            . JWT::urlsafeB64Encode(json_encode($payload)) . '.signature';
    }

    private function notification(string $type, ?array $transaction, string $bundleId = 'com.bangertstudio.manover'): array
    {
        $data = ['bundleId' => $bundleId, 'environment' => 'Sandbox'];
        if ($transaction !== null) {
            $data['signedTransactionInfo'] = $this->jws($transaction);
        }

        return ['signedPayload' => $this->jws(['notificationType' => $type, 'data' => $data])];
    }

    private function transaction(string $id, string $expires = '2026-10-27 14:00:00'): array
    {
        return [
            'transactionId' => $id,
            'originalTransactionId' => 'orig-1',
            'productId' => 'executor_1m',
            'appAccountToken' => $this->request->uuid,
            'expiresDate' => Carbon::parse($expires)->getTimestampMs(),
        ];
    }

    public function test_subscribed_marks_invoice_paid(): void
    {
        $this->postJson('/api/webhooks/apple', $this->notification('SUBSCRIBED', $this->transaction('tx-1')))
            ->assertOk();

        $invoice = $this->request->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame('tx-1', $invoice->store_transaction_id);
    }

    public function test_did_renew_creates_new_invoice(): void
    {
        $this->postJson('/api/webhooks/apple', $this->notification('SUBSCRIBED', $this->transaction('tx-1')));
        $this->postJson('/api/webhooks/apple', $this->notification('DID_RENEW', $this->transaction('tx-2', '2026-11-27 14:00:00')))
            ->assertOk();

        $this->assertSame(2, Invoice::where('status', Invoice::STATUS_PAID)->count());
    }

    public function test_refund_cancels_invoice(): void
    {
        $this->postJson('/api/webhooks/apple', $this->notification('SUBSCRIBED', $this->transaction('tx-1')));
        $this->postJson('/api/webhooks/apple', $this->notification('REFUND', $this->transaction('tx-1')))
            ->assertOk();

        $this->assertSame(Invoice::STATUS_CANCELED, $this->request->fresh()->status);
    }

    public function test_foreign_bundle_is_ignored(): void
    {
        $this->postJson('/api/webhooks/apple', $this->notification('SUBSCRIBED', $this->transaction('tx-1'), 'com.other.app'))
            ->assertOk();

        $this->assertSame(Invoice::STATUS_CREATED, $this->request->fresh()->status);
    }

    public function test_test_notification_returns_200(): void
    {
        $this->postJson('/api/webhooks/apple', $this->notification('TEST', null))->assertOk();
    }

    public function test_missing_payload_returns_400(): void
    {
        $this->postJson('/api/webhooks/apple', [])->assertStatus(400);
    }

    public function test_malformed_payload_returns_400(): void
    {
        $this->postJson('/api/webhooks/apple', ['signedPayload' => 'not-a-jws'])->assertStatus(400);
    }
}
