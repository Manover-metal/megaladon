<?php

namespace Tests\Feature\Concerns;

use App\Models\City;
use App\Models\Executor;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/** Пользователи, исполнители, магазины и тарифы для тестов подписок. */
trait MakesSubscribers
{
    private ?City $subscribersCity = null;

    /** Колонка users.city_id без дефолта — нужен реальный город. */
    protected function city(): City
    {
        return $this->subscribersCity ??= City::create(['name' => 'Алматы']);
    }

    protected function makeUser(string $phone): User
    {
        $user = User::create([
            'name' => 'User ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
            'city_id' => $this->city()->id,
        ]);

        // Не в $fillable: колонками управляет только само приложение.
        $user->forceFill([
            'push_notifications' => true,
            'device_token' => 'device-token-' . $phone,
        ])->save();

        return $user->refresh();
    }

    protected function makeExecutor(User $user): Executor
    {
        // lat/lon/full_address в схеме NOT NULL.
        return Executor::create([
            'user_id' => $user->id,
            'name' => 'Цех',
            'lat' => 43.238949,
            'lon' => 76.889709,
            'full_address' => 'Алматы, ул. Промышленная, 1',
        ]);
    }

    protected function makeStore(User $user): Store
    {
        return Store::create([
            'user_id' => $user->id,
            'type_id' => 1,
            'name' => 'Металлобаза',
            'bin' => '123456789012',
            'city_id' => $this->city()->id,
            'lat' => 43.238949,
            'lon' => 76.889709,
            'full_address' => 'Алматы, ул. Складская, 2',
        ]);
    }

    protected function makePlan(string $type, float $price = 5000, array $extra = []): Subscription
    {
        return Subscription::create([
            'type' => $type,
            'validity' => 1,
            'price' => $price,
        ] + $extra);
    }
}
