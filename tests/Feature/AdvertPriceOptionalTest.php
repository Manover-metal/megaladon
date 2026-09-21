<?php

namespace Tests\Feature;

use App\Models\AdCategory;
use App\Models\Advert;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Цена у объявления необязательна: продавец может не называть её сразу.
 * Валидация это разрешала и раньше, но колонка в БД была NOT NULL, и запрос
 * падал с "Column 'price' cannot be null" уже на уровне SQL.
 */
class AdvertPriceOptionalTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Test',
            'phone' => '+77001234567',
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'type' => 'advert',
            'title' => 'Токарные работы',
            'description' => 'Описание',
            'category_id' => AdCategory::create(['name' => 'Металлообработка'])->id,
            'city_id' => City::create(['name' => 'Алматы'])->id,
        ], $extra);
    }

    public function test_advert_is_created_without_price(): void
    {
        $token = $this->makeUser()->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/adverts', $this->payload());

        $response->assertOk();
        $this->assertNull(Advert::firstOrFail()->price);
    }

    public function test_advert_is_created_with_price(): void
    {
        $token = $this->makeUser()->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/adverts', $this->payload(['price' => 20000]));

        $response->assertOk();
        $this->assertEquals(20000, (int) Advert::firstOrFail()->price);
    }

    public function test_advert_without_price_is_presented_as_null(): void
    {
        $advert = Advert::create($this->payload([
            'user_id' => $this->makeUser()->id,
        ]));

        $response = $this->getJson("/api/adverts/{$advert->id}");

        $response->assertOk();
        $this->assertNull($response->json('advert.price'));
    }

    public function test_advert_with_price_is_presented_as_number(): void
    {
        $advert = Advert::create($this->payload([
            'user_id' => $this->makeUser()->id,
            'price' => 20000,
        ]));

        $response = $this->getJson("/api/adverts/{$advert->id}");

        $response->assertOk();
        $this->assertEquals(20000.0, $response->json('advert.price'));
    }
}
