<?php

namespace App\Listeners;

use App\Events\OrderArchivedEvent;
use App\Services\v1\PushService;
use Illuminate\Support\Facades\Log;

class OrderArchivedListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(OrderArchivedEvent $event): void
    {
        $order = $event->order;
        $user = $order->executor?->user;
        if (!$user) {
            return;
        }

        try {
            $this->push->send(
                $user,
                'Заказ №' . $order->id,
                'Заказчик снял заказ',
                ['type' => 'order_archived', 'order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            Log::error(__METHOD__ . ' push failed for order #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
