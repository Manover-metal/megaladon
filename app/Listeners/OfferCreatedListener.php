<?php

namespace App\Listeners;

use App\Events\OfferCreatedEvent;
use App\Services\v1\PushService;

class OfferCreatedListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(OfferCreatedEvent $event): void
    {
        $this->push->send(
            $event->order->user,
            'Новое предложение на заказ №' . $event->order->id,
            'Кто-то откликнулся на ваш заказ',
            ['type' => 'offer_created', 'order_id' => $event->order->id],
        );
    }
}
