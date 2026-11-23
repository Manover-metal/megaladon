<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\PhoneConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $phone = '+77001234567'): User
    {
        // Мутатор сам хеширует пароль; Hash::make здесь давал двойной хеш.
        return User::create([
            'name' => 'Test',
            'phone' => $phone,
            'password' => 'oldpassword',
            'is_phone_confirmed' => true,
            'city_id' => City::firstOrCreate(['name' => 'Алматы'])->id,
        ]);
    }

    // Отладочный режим (NO_SEND_SMS): SMS не шлём, код постоянный — его можно
    // отдать в ответе, чтобы войти без SMS при разработке.
    public function test_forgot_password_in_debug_mode_returns_debug_code(): void
    {
        config(['smsc.no_send_sms' => true, 'smsc.debug_code' => '101010']);
        $this->makeUser();

        $this->postJson('/api/auth/forgot-password', ['phone' => '+77001234567'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verification_code', '101010');
    }

    // SMS не ушла (шлюз не настроен, кончился баланс): код в ответ НЕ попадает.
    // Иначе сбросить пароль можно было бы, зная только чужой номер.
    public function test_forgot_password_never_leaks_code_when_sms_fails(): void
    {
        config(['smsc.no_send_sms' => false, 'smsc.api_key' => null, 'smsc.login' => '', 'smsc.password' => '']);
        $this->makeUser();

        $response = $this->postJson('/api/auth/forgot-password', ['phone' => '+77001234567'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull($response->json('verification_code'));
        // Код при этом сохранён и сработает, если SMS всё же дойдёт.
        $this->assertSame(6, strlen(PhoneConfirmation::latest('id')->value('code')));
    }

    public function test_forgot_password_404_for_unknown_user(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'phone' => '+77009999999',
        ]);

        $response->assertStatus(404);
    }

    public function test_reset_password_with_valid_code_changes_password(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_reset_password_uses_latest_code_when_multiple_exist(): void
    {
        $user = $this->makeUser();
        // Стойкая (старая) запись с другим кодом — например, от прошлой регистрации.
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '999999',
        ]);
        // Свежая запись от forgot-password.
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_reset_password_rejects_wrong_code(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_validation_requires_confirmation(): void
    {
        $user = $this->makeUser();
        PhoneConfirmation::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => '101010',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '101010',
            'password' => 'newpassword',
        ]);

        $response->assertStatus(422);
    }
}
