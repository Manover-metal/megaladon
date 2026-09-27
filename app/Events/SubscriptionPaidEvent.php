<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaidEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $user_id, public string $expired_at)
    {
    }
}
