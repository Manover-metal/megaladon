<?php

namespace App\Services\v1;

use App\Events\SubscriptionPaidEvent;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Оплата из вебхуков App Store и Google Play. Контроллеры разбирают формат
 * своего магазина и зовут сюда.
 */
class StorePaymentService
{
    /**
     * Первая оплата закрывает заявку, следующие (продления) — новый
     * оплаченный инвойс у того же владельца.
     */
    public function paid(string $uuid, string $transactionId, Carbon $expiresAt, array $meta): void
    {
        $paid = DB::transaction(function () use ($uuid, $transactionId, $expiresAt, $meta) {
            $origin = Invoice::where('uuid', $uuid)->lockForUpdate()->first();
            // Владельца могли удалить — магазин повторял бы вебхук вечно, отвечаем 200.
            if (!$origin || !$origin->invoiceable) {
                Log::warning('Store payment: invoice or owner not found', compact('uuid', 'transactionId'));
                return null;
            }

            // Магазины доставляют уведомления повторно.
            if (Invoice::where('store_transaction_id', $transactionId)->exists()) {
                return null;
            }

            $attributes = [
                'status' => Invoice::STATUS_PAID,
                'store_transaction_id' => $transactionId,
                // expired_at — дата, подписка активна, пока expired_at > сегодня:
                // округляем вверх, чтобы не отрезать последний оплаченный день.
                'expired_at' => $expiresAt->copy()->ceilDay()->toDateString(),
                'meta' => json_encode($meta),
            ];

            if ($origin->status === Invoice::STATUS_CREATED) {
                $origin->update($attributes);
            } else {
                $origin->invoiceable->invoices()->create($attributes + [
                    'subscription_id' => $origin->subscription_id,
                    'payment_method' => $origin->payment_method,
                ]);
            }

            return [$origin->invoiceable->user_id, $attributes['expired_at']];
        });

        if ($paid) {
            event(new SubscriptionPaidEvent(...$paid));
        }
    }

    /** Возврат или отзыв: подписка откатывается к предыдущему оплаченному инвойсу. */
    public function refunded(string $transactionId): void
    {
        Invoice::where('store_transaction_id', $transactionId)
            ->update(['status' => Invoice::STATUS_CANCELED]);
    }
}
