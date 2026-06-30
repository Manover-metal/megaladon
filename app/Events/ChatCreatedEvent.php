<?php

namespace App\Events;

use App\Models\Chat;
use App\Presenters\v1\ChatPresenter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;
    public Chat $chat;

    public function __construct($user_id, Chat $chat)
    {
        $this->user_id = $user_id;
        $this->chat = $chat;
    }

    public function broadcastAs()
    {
        return 'new_message';
    }

    public function broadcastOn()
    {
        return new Channel('userChats.' . $this->user_id);
    }

    public function broadcastWith()
    {
        return [
            'chat' => (new ChatPresenter($this->chat->load('lastMessage', 'chatable')))->chatList(),
        ];
    }
}
