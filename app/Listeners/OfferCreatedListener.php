<?php

namespace App\Listeners;

use App\Services\v1\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OfferCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->order->user;
        (new PushNotificationService())->sendNotification($user->device_token,
            'Новое предложение на заказ №'. $event->order->id,
            'Кто-то откликнулся на ваш заказ',
            [
                'order_id' => $event->order->id
            ]);
    }
}
