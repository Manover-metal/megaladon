<?php

namespace App\Listeners;

use App\Events\SubscriptionPaidEvent;
use App\Models\User;
use App\Services\v1\PushService;
use Carbon\Carbon;

class SubscriptionPaidListener
{
    public function __construct(private PushService $push)
    {
    }

    public function handle(SubscriptionPaidEvent $event): void
    {
        $user = User::find($event->user_id);
        if (!$user) {
            return;
        }

        // По type приложение перечитывает профиль — статус подписки обновится.
        $this->push->send(
            $user,
            'Подписка оплачена',
            'Подписка активна до ' . Carbon::parse($event->expired_at)->format('d.m.Y'),
            ['type' => 'subscription_paid'],
        );
    }
}
