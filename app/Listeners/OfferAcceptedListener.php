<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\v1\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OfferAcceptedListener
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
        $user = User::find($event->user_id);
        (new PushNotificationService())->sendNotification($user->device_token,
            'Заказ №'. $event->order_id,
            'Ваше предложение принято!',
            [
                'order_id' => $event->order_id,
            ]);
    }
}
