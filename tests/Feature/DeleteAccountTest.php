<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        // Мутатор сам хеширует пароль; Hash::make здесь давал двойной хеш.
        return User::create([
            'name' => 'Test',
            'phone' => '+77001234567',
            'password' => 'secret123',
            'is_phone_confirmed' => true,
            'city_id' => City::create(['name' => 'Алматы'])->id,
        ]);
    }

    public function test_delete_account_with_correct_password_soft_deletes_user(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/user/delete-account', ['password' => 'secret123']);

        $response->assertStatus(200)->assertJsonPath('data.success', true);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // 422, а не 401: на 401 приложение считает токен мёртвым и выходит из аккаунта.
    public function test_delete_account_with_wrong_password_returns_422_and_keeps_session(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/user/delete-account', ['password' => 'wrong']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_delete_account_without_token_is_unauthorized(): void
    {
        $response = $this->deleteJson('/api/user/delete-account', ['password' => 'secret123']);
        $response->assertStatus(401);
    }
}
