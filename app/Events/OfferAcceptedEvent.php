<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferAcceptedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;
    public $order_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user_id, $order_id)
    {
        $this->user_id = $user_id;
        $this->order_id = $order_id;
    }

}
