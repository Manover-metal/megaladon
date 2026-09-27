<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
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
