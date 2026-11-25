<?php

namespace App\Providers;

use App\Models\Advert;
use App\Models\Executor;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Store;
use App\Models\User;
use App\Models\UserBlock;
use App\Observers\OrderObserver;
use App\Observers\RatingObserver;
use App\Services\TelegramService;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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

        // Sanctum 2 не знает про expires_at у токена — проверяем сами.
        // null — вечный токен (админка).
        Sanctum::authenticateAccessTokensUsing(fn ($token, bool $isValid) =>
            $isValid && (is_null($token->expires_at) || Carbon::parse($token->expires_at)->isFuture()));

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

        Advert::created(fn (Advert $a) => TelegramService::notify('advert',
            "📢 Новое объявление: $a->title\nЦена: $a->price\nАвтор: " . $who($a->user), "advert/$a->id"));
        Service::created(fn (Service $s) => TelegramService::notify('service',
            "🧰 Новая услуга: $s->title\nЦена: $s->price\nАвтор: " . $who($s->user), "service/$s->id"));

        UserBlock::created(fn (UserBlock $b) => TelegramService::notify('user_block',
            "🚫 Блокировка\n" . $who($b->user) . ' заблокировал(а) ' . $who($b->blockedUser), "user-block/$b->id"));
    }
}
