<?php

namespace App\Listeners;

use App\Events\OfferAcceptedEvent;
use App\Models\User;
use App\Services\v1\PushService;

class OfferAcceptedListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(OfferAcceptedEvent $event): void
    {
        $user = User::find($event->user_id);
        if (!$user) {
            return;
        }

        $this->push->send(
            $user,
            'Заказ №' . $event->order_id,
            'Ваше предложение принято!',
            ['type' => 'offer_accepted', 'order_id' => $event->order_id],
        );
    }
}
