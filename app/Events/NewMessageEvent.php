<?php

namespace App\Events;

use App\Presenters\v1\ChatPresenter;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $excludeUsers;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($message, $excludeUsers = [])
    {
        $this->message = $message;
        $this->excludeUsers = $excludeUsers;
    }

    public function broadcastOn()
    {
        return [
            new Channel('chat.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs()
    {
        return 'new_message';
    }

    public function broadcastWith()
    {
        return [
            'message' => (new ChatPresenter($this->message))->messages(),
        ];
    }
}
