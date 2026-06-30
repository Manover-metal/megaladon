<?php

namespace App\Listeners;

use App\Models\ChatUser;
use App\Models\User;
use App\Services\v1\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NewMessageListener
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
        $members = ChatUser::where('chat_id', $event->message->chat_id)->whereNotIn('user_id', $event->excludeUsers)->pluck('user_id');
        $tokens = User::whereIn('id', $members->toArray())->pluck('device_token')->toArray();
        (new PushNotificationService())->sendNotification($tokens,
            'Новое сообщение',
            $event->message->message,
            [
                'chat_id' => $event->message->chat_id,
            ]);
    }
}
