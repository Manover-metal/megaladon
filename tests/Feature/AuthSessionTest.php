<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Приложение выходит из аккаунта на любой 401. Поэтому 401 допустим только
 * для мёртвого токена: неверный пароль в формах — 422, а выход на одном
 * устройстве не должен убивать сессии на других.
 */
class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $city = City::create(['name' => 'Алматы']);
        // Мутатор сам хеширует пароль.
        $this->user = User::create([
            'name' => 'Test',
            'phone' => '+77001234567',
            'password' => 'secret123',
            'is_phone_confirmed' => 1,
            'city_id' => $city->id,
        ]);
    }

    /** Ручка под auth:api: живой токен — 200, мёртвый — 401. */
    private function profile(string $token)
    {
        // Гард кэширует пользователя между запросами одного теста.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson('/api/blocks');
    }

    public function test_logout_revokes_only_current_device_token(): void
    {
        $phone = $this->user->createToken('api')->plainTextToken;
        $tablet = $this->user->createToken('api')->plainTextToken;

        $this->withToken($phone)->deleteJson('/api/auth/logout')->assertOk();

        $this->profile($phone)->assertStatus(401);
        $this->profile($tablet)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_change_password_with_wrong_old_password_is_422_and_keeps_session(): void
    {
        $token = $this->user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/user/change-password', [
            'old_password' => 'wrong',
            'password' => 'newpass1',
            'password_confirmation' => 'newpass1',
        ])->assertStatus(422);

        $this->profile($token)->assertOk();
    }

    public function test_change_phone_with_wrong_password_is_422_and_keeps_session(): void
    {
        $token = $this->user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/user/change-phone/start', [
            'new_phone' => '+77007654321',
            'password' => 'wrong',
        ])->assertStatus(422);

        $this->profile($token)->assertOk();
    }

    public function test_dead_token_still_gets_401(): void
    {
        $this->profile('1|not-a-real-token')->assertStatus(401);
    }

    // Ручки не под auth:api (гостя режет authorize() FormRequest'а) тоже
    // должны отдавать 401 — раньше было 403, и приложение не выходило.
    public function test_dead_token_on_form_request_route_gets_401(): void
    {
        $this->withToken('1|not-a-real-token')->getJson('/api/user/profile')->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken('1|not-a-real-token')->getJson('/api/order/my')->assertStatus(401);
    }

    public function test_guest_without_token_gets_401(): void
    {
        $this->getJson('/api/user/profile')->assertStatus(401);
    }

    public function test_authenticated_but_forbidden_stays_403(): void
    {
        $token = $this->user->createToken('api')->plainTextToken;

        // Обычный пользователь в админке: токен жив, доступа нет.
        $this->withToken($token)->getJson('/api/admin/me')->assertStatus(403);
        $this->profile($token)->assertOk();
    }
}
