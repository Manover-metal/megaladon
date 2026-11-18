<?php

namespace App\Providers;

use App\Models\Advert;
use App\Models\Executor;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Store;
use App\Models\User;
use App\Models\UserBlock;
use App\Observers\OrderObserver;
use App\Observers\RatingObserver;
use App\Services\TelegramService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Order::observe(OrderObserver::class);
        Rating::observe(RatingObserver::class);

        $this->telegramEvents();
    }

    private function telegramEvents(): void
    {
        $who = fn (?User $u) => $u ? trim("$u->name $u->phone") : '—';

        User::created(fn (User $u) => TelegramService::notify('user',
            "👤 Новый пользователь\n" . $who($u), "user/$u->id"));

        Store::created(fn (Store $s) => TelegramService::notify('store',
            "🏪 Новый магазин: $s->name\nВладелец: " . $who($s->user), "store/$s->id"));

        Executor::created(fn (Executor $e) => TelegramService::notify('executor',
            "🛠 Новый исполнитель: $e->name\nВладелец: " . $who($e->user), "executor/$e->id"));

        Order::created(fn (Order $o) => TelegramService::notify('order',
            "📦 Новый заказ: $o->title\nБюджет: до $o->price_max\nЗаказчик: " . $who($o->user), "order/$o->id"));

        Advert::created(function (Advert $a) use ($who) {
            $isService = $a->type === Advert::TYPE_SERVICE;
            TelegramService::notify($isService ? 'service' : 'advert',
                ($isService ? '🧰 Новая услуга: ' : '📢 Новое объявление: ') . "$a->title\nЦена: $a->price\nАвтор: " . $who($a->user),
                "advert/$a->id");
        });

        UserBlock::created(fn (UserBlock $b) => TelegramService::notify('user_block',
            "🚫 Блокировка\n" . $who($b->user) . ' заблокировал(а) ' . $who($b->blockedUser), "user-block/$b->id"));
    }
}
