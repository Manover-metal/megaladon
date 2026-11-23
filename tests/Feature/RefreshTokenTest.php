<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.access_ttl' => 3600, 'sanctum.refresh_ttl' => 86400 * 30]);
        $this->user = User::create([
            'name' => 'Test',
            'phone' => '+77001234567',
            'password' => 'secret123',
            'is_phone_confirmed' => 1,
            'city_id' => City::create(['name' => 'Алматы'])->id,
        ]);
    }

    private function login(): array
    {
        return $this->withHeaders(['X-Auth-Refresh' => '1'])
            ->postJson('/api/auth/login', ['phone' => '+77001234567', 'password' => 'secret123'])
            ->assertOk()->json();
    }

    private function blocks(string $token)
    {
        $this->app['auth']->forgetGuards();
        return $this->withToken($token)->getJson('/api/blocks');
    }

    private function refresh(string $refresh)
    {
        $this->app['auth']->forgetGuards();
        return $this->withHeaders(['Authorization' => '', 'X-Auth-Refresh' => '1'])
            ->postJson('/api/auth/refresh', ['refresh_token' => $refresh]);
    }

    public function test_login_issues_pair(): void
    {
        $body = $this->login();
        $this->assertNotEmpty($body['token']);
        $this->assertSame(64, strlen($body['refresh_token']));
        $this->assertSame(3600, $body['expires_in']);
        $this->blocks($body['token'])->assertOk();
    }

    // Старая версия приложения не шлёт X-Auth-Refresh и refresh не умеет:
    // ей токен на срок refresh, а не на час.
    public function test_old_app_without_refresh_gets_long_lived_token(): void
    {
        $body = $this->postJson('/api/auth/login', ['phone' => '+77001234567', 'password' => 'secret123'])
            ->assertOk()->json();
        $this->assertSame(86400 * 30, $body['expires_in']);
        $this->travel(2)->days();
        $this->blocks($body['token'])->assertOk();
    }

        public function test_access_token_expires(): void
    {
        $body = $this->login();
        $this->travel(3601)->seconds();
        $this->blocks($body['token'])->assertStatus(401);
    }

    public function test_refresh_rotates_pair(): void
    {
        $old = $this->login();
        $this->travel(3601)->seconds();

        $new = $this->refresh($old['refresh_token'])->assertOk()->json();

        $this->assertNotSame($old['token'], $new['token']);
        $this->blocks($new['token'])->assertOk();
        // Старая пара погашена целиком.
        $this->assertSame(1, PersonalAccessToken::count());
        $this->assertSame(1, RefreshToken::count());
        $this->refresh($old['refresh_token'])->assertStatus(401);
    }

    public function test_refresh_before_expiry_kills_old_access_token(): void
    {
        $old = $this->login();
        $this->refresh($old['refresh_token'])->assertOk();
        $this->blocks($old['token'])->assertStatus(401);
    }

    // Клиент шлёт один refresh, но если два всё же ушли одним токеном —
    // принят ровно один, второй получает 401, пара не размножается.
    public function test_same_refresh_token_works_once(): void
    {
        $old = $this->login();
        $first = $this->refresh($old['refresh_token']);
        $second = $this->refresh($old['refresh_token']);

        $first->assertOk();
        $second->assertStatus(401);
        $this->assertSame(1, RefreshToken::count());
    }

    public function test_expired_refresh_token_is_rejected(): void
    {
        $old = $this->login();
        $this->travel(86400 * 30 + 1)->seconds();
        $this->refresh($old['refresh_token'])->assertStatus(401);
        $this->assertSame(0, RefreshToken::count());
    }

    public function test_unknown_refresh_token_is_rejected(): void
    {
        $this->refresh(str_repeat('x', 64))->assertStatus(401);
        $this->refresh('')->assertStatus(422);
    }

    public function test_logout_revokes_device_refresh_only(): void
    {
        $phone = $this->login();
        $tablet = $this->login();

        $this->app['auth']->forgetGuards();
        $this->withToken($phone['token'])->deleteJson('/api/auth/logout')->assertOk();

        $this->refresh($phone['refresh_token'])->assertStatus(401);
        $this->refresh($tablet['refresh_token'])->assertOk();
    }

    public function test_ban_revokes_refresh(): void
    {
        $body = $this->login();
        $this->user->ban('spam');
        $this->refresh($body['refresh_token'])->assertStatus(401);
    }

    public function test_delete_account_revokes_refresh(): void
    {
        $body = $this->login();
        $this->app['auth']->forgetGuards();
        $this->withToken($body['token'])->deleteJson('/api/user/delete-account', ['password' => 'secret123'])->assertOk();
        $this->refresh($body['refresh_token'])->assertStatus(401);
    }

    public function test_legacy_token_is_upgraded_to_pair(): void
    {
        // Токен старой версии приложения: без expires_at и без refresh.
        $legacy = $this->user->createToken('api')->plainTextToken;

        $this->app['auth']->forgetGuards();
        $pair = $this->withToken($legacy)->postJson('/api/auth/upgrade-token')->assertOk()->json();

        $this->assertSame(64, strlen($pair['refresh_token']));
        $this->blocks($pair['token'])->assertOk();
        $this->blocks($legacy)->assertStatus(401);
    }

    public function test_admin_tokens_do_not_expire_and_cannot_be_upgraded(): void
    {
        $this->user->forceFill(['role' => 'admin'])->save();
        $admin = $this->user->createToken('admin')->plainTextToken;
        $this->travel(365)->days();

        $this->app['auth']->forgetGuards();
        $this->withToken($admin)->getJson('/api/admin/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($admin)->postJson('/api/auth/upgrade-token')->assertStatus(401);
    }
}
