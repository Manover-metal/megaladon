<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Billing\GooglePlayClient;
use App\Services\v1\StorePaymentService;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Вебхуки магазинов. Здесь только разбор формата; что делать с оплатой,
 * решает StorePaymentService. 200 на всё, что повторять бессмысленно, —
 * иначе магазин будет слать уведомление снова.
 */
class StoreWebhookController extends Controller
{
    // subscriptionNotification.notificationType
    private const GOOGLE_RECOVERED = 1;
    private const GOOGLE_RENEWED = 2;
    private const GOOGLE_PURCHASED = 4;
    private const GOOGLE_REVOKED = 12;

    public function __construct(private StorePaymentService $payments)
    {
    }

    /** App Store Server Notifications V2. */
    public function apple(Request $request)
    {
        $notification = $this->applePayload($request->input('signedPayload'));
        if ($notification === null) {
            return response()->json(['message' => 'signedPayload required'], 400);
        }

        $data = $notification['data'] ?? [];
        $type = $notification['notificationType'] ?? '';
        $transaction = $this->applePayload($data['signedTransactionInfo'] ?? null);

        if (($data['bundleId'] ?? null) !== config('billing.apple.bundle_id')) {
            Log::warning('Apple webhook: foreign bundleId', ['bundleId' => $data['bundleId'] ?? null]);
        } elseif ($transaction && in_array($type, ['SUBSCRIBED', 'DID_RENEW'], true) && !empty($transaction['appAccountToken'])) {
            $this->payments->paid(
                $transaction['appAccountToken'],
                (string) $transaction['transactionId'],
                Carbon::createFromTimestampMs($transaction['expiresDate']),
                $transaction,
            );
        } elseif ($transaction && in_array($type, ['REFUND', 'REVOKE'], true)) {
            $this->payments->refunded((string) $transaction['transactionId']);
        } else {
            Log::info('Apple webhook skipped', ['type' => $type, 'subtype' => $notification['subtype'] ?? null]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Real-time developer notifications через Pub/Sub push. Номера заявки и
     * срока в уведомлении нет — берём их из Google Play API; поддельное
     * уведомление оплату, которой нет в Google, не создаст.
     */
    public function google(Request $request, string $secret, GooglePlayClient $google)
    {
        $expected = config('billing.google.webhook_secret');
        if (!$expected || !hash_equals($expected, $secret)) {
            abort(404);
        }

        $message = json_decode(base64_decode((string) $request->input('message.data')), true) ?: [];
        $notification = $message['subscriptionNotification'] ?? null;
        $type = (int) ($notification['notificationType'] ?? 0);
        $handled = [self::GOOGLE_RECOVERED, self::GOOGLE_RENEWED, self::GOOGLE_PURCHASED, self::GOOGLE_REVOKED];

        if (($message['packageName'] ?? null) !== config('billing.google.package_name')
            || !in_array($type, $handled, true)) {
            Log::info('Google webhook skipped', ['message' => $message]);
            return response()->json(['success' => true]);
        }

        $token = $notification['purchaseToken'];
        // Ошибка API → 500, Pub/Sub повторит доставку.
        $subscription = $google->subscription($token);
        $orderId = $subscription['latestOrderId'];

        if ($type === self::GOOGLE_REVOKED) {
            $this->payments->refunded($orderId);
            return response()->json(['success' => true]);
        }

        $line = $subscription['lineItems'][0];
        if (($subscription['acknowledgementState'] ?? null) === 'ACKNOWLEDGEMENT_STATE_PENDING') {
            $google->acknowledge($line['productId'], $token);
        }

        $uuid = $subscription['externalAccountIdentifiers']['obfuscatedExternalAccountId'] ?? null;
        if ($uuid) {
            $this->payments->paid($uuid, $orderId, Carbon::parse($line['expiryTime']), $subscription);
        } else {
            Log::warning('Google webhook: purchase without invoice uuid', ['orderId' => $orderId]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Payload подписанных данных App Store (JWS). null — не JWS.
     *
     * ponytail: подпись НЕ проверяется — любой, кто знает uuid заявки, может
     * прислать поддельную оплату. До боевого URL в App Store Connect добавить
     * проверку цепочки x5c до Apple Root CA G3 (или сверку транзакции через
     * App Store Server API).
     */
    private function applePayload($jws): ?array
    {
        $parts = is_string($jws) ? explode('.', $jws) : [];
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(JWT::urlsafeB64Decode($parts[1]), true);
        return is_array($payload) ? $payload : null;
    }
}
