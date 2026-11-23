<?php

namespace Tests\Feature;

use App\Models\Advert;
use App\Models\City;
use App\Models\Executor;
use App\Models\Order;
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
        $this->assertSame(25, Order::count());
        $this->assertSame(15, Advert::where('type', Advert::TYPE_ADVERT)->count());
        $this->assertSame(10, Advert::where('type', Advert::TYPE_SERVICE)->count());

        $this->assertEquals([
            Order::STATUS_MODERATE => 2,
            Order::STATUS_ACTIVE => 12,
            Order::STATUS_HAS_EXECUTOR => 5,
            Order::STATUS_COMPLETED => 4,
            Order::STATUS_ARCHIVE => 2,
        ], Order::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->map(fn ($c) => (int) $c)->sortKeys()->all());

        // Исполнитель есть ровно у выбранных и выполненных.
        $this->assertSame(9, Order::where('executor_id', '>', 0)->count());

        // Только три города.
        $cityIds = City::whereIn('name', ['Алматы', 'Астана', 'Караганды'])->pluck('id');
        foreach (['users', 'orders', 'adverts'] as $table) {
            $this->assertSame(0, DB::table($table)->whereNotNull('city_id')->whereNotIn('city_id', $cityIds)->count(), $table);
        }

        // У каждой карточки есть фото, и файл реально лежит в storage.
        foreach ([Order::class, Advert::class] as $model) {
            $model::with('media')->get()->each(function ($item) {
                $this->assertNotEmpty($item->media, class_basename($item) . " #{$item->id} без фото");
                $this->assertFileExists(storage_path('app/public/' . substr($item->media->first()->storage_link, strlen('/storage/'))));
            });
        }

        // У каждого исполнителя есть рейтинг.
        $this->assertSame(0, Executor::doesntHave('ratings')->count());

        // Под демо-заказчиком можно войти.
        $this->postJson('/api/auth/login', ['phone' => '+77010000001', 'password' => DemoSeeder::PASSWORD])
            ->assertOk()->assertJsonStructure(['token']);
    }
}
