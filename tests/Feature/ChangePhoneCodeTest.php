<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\PhoneConfirmation;
use App\Models\User;
use App\Repositories\PhoneConfirmationRepo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Смена телефона страдала тем же дефектом, что и подтверждение регистрации:
 * getByUserIdAndPhone() делал ->first() без сортировки и возвращал самый
 * первый код, выданный на пару (пользователь, номер), а использованные коды
 * не удалялись. В сумме это позволяло привязать номер старым кодом.
 */
class ChangePhoneCodeTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PHONE = '+77071111111';
    private const NEW_PHONE = '+77072222222';

    private function makeUser(): User
    {
        $city = City::create(['name' => 'Алматы']);

        return User::create([
            'name' => 'Тест',
            'phone' => self::OLD_PHONE,
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
            'city_id' => $city->id,
        ]);
    }

    private function issueCodes(User $user, string ...$codes): void
    {
        $repo = new PhoneConfirmationRepo();
        foreach ($codes as $code) {
            $repo->store($user, self::NEW_PHONE, $code);
        }
    }

    private function endChange(User $user, string $code)
    {
        return $this->withToken($user->createToken('api')->plainTextToken)
            ->postJson('/api/user/change-phone/end', [
                'phone' => self::NEW_PHONE,
                'code' => $code,
            ]);
    }

    public function test_latest_code_is_accepted(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '101010', '550341');

        $this->endChange($user, '550341')->assertOk();
        $this->assertSame(self::NEW_PHONE, $user->fresh()->phone);
    }

    public function test_previous_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '101010', '550341');

        $this->endChange($user, '101010')->assertStatus(406);
        $this->assertSame(self::OLD_PHONE, $user->fresh()->phone);
    }

    public function test_code_cannot_be_reused(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '550341');

        $this->endChange($user, '550341')->assertOk();

        $this->assertSame(
            0,
            PhoneConfirmation::where('phone', self::NEW_PHONE)->count(),
            'после смены номера действующих кодов остаться не должно'
        );
    }
}
