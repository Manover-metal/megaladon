<?php

namespace Tests\Feature;

use App\Models\Advert;
use App\Models\City;
use App\Models\Executor;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\CitySeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_matches_the_brief(): void
    {
        $this->seed(CitySeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(10, Executor::count());
        $this->assertSame(20, User::where('phone', 'like', '+77010%')->count());
        $this->assertSame(14, Order::count());

        // Объявления, услуги и магазины в витрину не входят.
        $this->assertSame(0, Advert::count());
        $this->assertSame(0, Store::count());

        $this->assertEquals([
            Order::STATUS_MODERATE => 1,
            Order::STATUS_ACTIVE => 8,
            Order::STATUS_HAS_EXECUTOR => 2,
            Order::STATUS_COMPLETED => 2,
            Order::STATUS_ARCHIVE => 1,
        ], Order::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->map(fn ($c) => (int) $c)->sortKeys()->all());

        // Исполнитель есть ровно у выбранных и выполненных.
        $this->assertSame(4, Order::where('executor_id', '>', 0)->count());

        // Заказы распределены по шести городам, и только по ним.
        $names = ['Алматы', 'Астана', 'Караганды', 'Шымкент', 'Павлодар', 'Костанай'];
        $cityIds = City::whereIn('name', $names)->pluck('id');
        foreach (['users', 'orders'] as $table) {
            $this->assertSame(0, DB::table($table)->whereNotNull('city_id')->whereNotIn('city_id', $cityIds)->count(), $table);
        }
        $this->assertCount(6, Order::distinct()->pluck('city_id'));

        // У каждого заказа есть фото, и файл реально лежит в storage.
        Order::with('media')->get()->each(function ($order) {
            $this->assertNotEmpty($order->media, "Order #{$order->id} без фото");
            $this->assertFileExists(storage_path('app/public/' . substr($order->media->first()->storage_link, strlen('/storage/'))));
        });

        // У каждого исполнителя есть рейтинг.
        $this->assertSame(0, Executor::doesntHave('ratings')->count());

        // Под демо-заказчиком можно войти.
        $this->postJson('/api/auth/login', ['phone' => '+77010000001', 'password' => DemoSeeder::PASSWORD])
            ->assertOk()->assertJsonStructure(['token']);
    }
}
