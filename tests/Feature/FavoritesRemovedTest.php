<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

/** Избранных исполнителей больше нет: ни таблицы, ни маршрутов. */
class FavoritesRemovedTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    public function test_table_and_routes_are_gone(): void
    {
        $this->assertFalse(Schema::hasTable('favorites'));

        $user = $this->makeUser('+77001234567');
        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/executor/favorite')->assertStatus(404);
    }
}
