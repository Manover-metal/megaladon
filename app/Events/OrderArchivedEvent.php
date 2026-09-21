<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Заказ снят с публикации: статус сменился на ARCHIVE. Для исполнителя это
 * значит, что работа отменена. Диспатчится из OrderObserver при сохранении
 * модели.
 */
class OrderArchivedEvent
{
    use Dispatchable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
