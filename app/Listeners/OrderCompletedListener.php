<?php

namespace App\Listeners;

use App\Events\OrderCompletedEvent;
use App\Services\v1\PushService;
use Illuminate\Support\Facades\Log;

class OrderCompletedListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(OrderCompletedEvent $event): void
    {
        $order = $event->order;
        $user = $order->executor?->user;
        if (!$user) {
            return;
        }

        // Пуш — вспомогательное действие: его сбой не должен ломать
        // завершение заказа, которое при QUEUE_CONNECTION=sync выполняется
        // в том же запросе.
        try {
            $this->push->send(
                $user,
                'Заказ №' . $order->id,
                'Заказчик подтвердил выполнение работы',
                ['type' => 'order_completed', 'order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' push failed for order #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
