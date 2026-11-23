<?php

namespace App\Listeners;

use App\Events\SubscriptionPaidEvent;
use App\Models\Subscription;
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
        // Подписки выключены — о них ничего не сообщаем. Продление у старых
        // подписчиков при этом всё равно записывается (StorePaymentService).
        if (!Subscription::enabled()) {
            return;
        }

        $user = User::find($event->user_id);
        if (!$user) {
            return;
        }

        $until = Carbon::parse($event->expired_at)->format('d.m.Y');

        // По type приложение перечитывает профиль — статус подписки обновится.
        // Пробный период — не оплата: «оплачена» человек принял бы за списание.
        $this->push->send(
            $user,
            $event->is_trial ? 'Пробный период начался' : 'Подписка оплачена',
            $event->is_trial
                ? "Бесплатно до $until. Затем подписка продлится автоматически, отменить можно в настройках магазина."
                : "Подписка активна до $until",
            ['type' => 'subscription_paid'],
        );
    }
}
