<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Заказчик подтвердил, что работа выполнена: статус сменился с HAS_EXECUTOR
 * на COMPLETED. Диспатчится из OrderObserver при сохранении модели.
 */
class OrderCompletedEvent
{
    use Dispatchable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
