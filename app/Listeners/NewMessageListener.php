<?php

namespace App\Listeners;

use App\Events\NewMessageEvent;
use App\Models\ChatUser;
use App\Models\User;
use App\Services\v1\PushService;

class NewMessageListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(NewMessageEvent $event): void
    {
        $memberIds = ChatUser::where('chat_id', $event->message->chat_id)
            ->whereNotIn('user_id', $event->excludeUsers)
            ->pluck('user_id');

        $users = User::whereIn('id', $memberIds->toArray())->get();

        // У сообщения-вложения текст (message) пустой — в пуше показываем
        // пометку о файле, иначе PushService получит null вместо string.
        $body = $event->message->message ?: '📎 Файл';

        $this->push->sendToUsers(
            $users,
            'Новое сообщение',
            $body,
            ['type' => 'new_message', 'chat_id' => $event->message->chat_id],
        );
    }
}
