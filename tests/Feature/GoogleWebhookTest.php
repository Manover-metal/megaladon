<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Billing\GooglePlayClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

class GoogleWebhookTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private Invoice $request;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config([
            'billing.google.package_name' => 'com.bangertstudio.manover',
            'billing.google.webhook_secret' => 's3cret',
        ]);
        // Токен сервисного аккаунта в тесте не получить.
        $this->app->instance(GooglePlayClient::class, new class extends GooglePlayClient {
            protected function accessToken(): string
            {
                return 'test-token';
            }
        });

        $executor = $this->makeExecutor($this->makeUser('+77001110000'));
        $plan = $this->makePlan(Subscription::EXECUTOR, 5000, ['google_product_id' => 'executor_1m']);
        $this->request = $executor->invoices()->create([
            'subscription_id' => $plan->id,
            'payment_method' => Invoice::METHOD_GOOGLE,
        ]);
    }

    private function googleSubscription(string $orderId, string $ack = 'ACKNOWLEDGEMENT_STATE_PENDING'): array
    {
        return [
            'latestOrderId' => $orderId,
            'acknowledgementState' => $ack,
            'externalAccountIdentifiers' => ['obfuscatedExternalAccountId' => $this->request->uuid],
            'lineItems' => [['productId' => 'executor_1m', 'expiryTime' => '2026-10-27T14:00:00Z']],
        ];
    }

    /**
     * Ответы subscriptionsv2.get по очереди. Http::fake вызывается один раз на
     * тест: повторный вызов не перекрывает прежние заглушки — побеждает первая.
     */
    private function fakeGoogle(array ...$subscriptions): void
    {
        $sequence = Http::sequence();
        foreach ($subscriptions as $subscription) {
            $sequence->push($subscription);
        }

        Http::fake([
            '*subscriptionsv2*' => $sequence,
            '*:acknowledge' => Http::response('', 200),
        ]);
    }

    private function push(array $message, string $secret = 's3cret')
    {
        return $this->postJson('/api/webhooks/google/' . $secret, [
            'message' => ['data' => base64_encode(json_encode($message)), 'messageId' => '1'],
            'subscription' => 'projects/p/subscriptions/s',
        ]);
    }

    private function subscriptionMessage(int $type): array
    {
        return [
            'packageName' => 'com.bangertstudio.manover',
            'subscriptionNotification' => [
                'notificationType' => $type,
                'purchaseToken' => 'token-1',
                'subscriptionId' => 'executor_1m',
            ],
        ];
    }

    public function test_purchased_marks_paid_and_acknowledges(): void
    {
        $this->fakeGoogle($this->googleSubscription('GPA.1'));

        $this->push($this->subscriptionMessage(4))->assertOk();

        $invoice = $this->request->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame('GPA.1', $invoice->store_transaction_id);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/purchases/subscriptions/executor_1m/tokens/token-1:acknowledge'));
    }

    public function test_already_acknowledged_is_not_acknowledged_again(): void
    {
        $this->fakeGoogle($this->googleSubscription('GPA.1', 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED'));

        $this->push($this->subscriptionMessage(4))->assertOk();

        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), ':acknowledge'));
    }

    public function test_renewed_creates_new_invoice(): void
    {
        $this->fakeGoogle(
            $this->googleSubscription('GPA.1'),
            $this->googleSubscription('GPA.1..0', 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED'),
        );
        $this->push($this->subscriptionMessage(4));

        $this->push($this->subscriptionMessage(2))->assertOk();

        $this->assertSame(2, Invoice::where('status', Invoice::STATUS_PAID)->count());
    }

    public function test_revoked_cancels_invoice(): void
    {
        $this->fakeGoogle($this->googleSubscription('GPA.1'), $this->googleSubscription('GPA.1'));
        $this->push($this->subscriptionMessage(4));

        $this->push($this->subscriptionMessage(12))->assertOk();

        $this->assertSame(Invoice::STATUS_CANCELED, $this->request->fresh()->status);
    }

    public function test_wrong_secret_returns_404(): void
    {
        Http::fake();

        $this->push($this->subscriptionMessage(4), 'wrong')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_test_notification_is_skipped(): void
    {
        Http::fake();

        $this->push(['packageName' => 'com.bangertstudio.manover', 'testNotification' => ['version' => '1.0']])
            ->assertOk();

        Http::assertNothingSent();
    }

    public function test_google_api_error_returns_500_for_retry(): void
    {
        Http::fake(['*subscriptionsv2*' => Http::response('', 503)]);

        $this->push($this->subscriptionMessage(4))->assertStatus(500);

        $this->assertSame(Invoice::STATUS_CREATED, $this->request->fresh()->status);
    }
}
