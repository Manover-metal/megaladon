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
 * Подтверждение телефона должно проверять ПОСЛЕДНИЙ выданный код и гасить его
 * после использования.
 *
 * Раньше AuthService::confirmCode() брал код через getByPhone(), то есть
 * ->first() без сортировки: MySQL отдавал строку с наименьшим id, и проверялся
 * самый первый код, когда-либо выданный на номер. Коды при этом не удалялись,
 * поэтому на проде у номера накопилось восемь записей, и подтверждение
 * принимало только код из июньской строки — в том числе отладочный 101010,
 * записанный в период NO_SEND_SMS=true.
 */
class PhoneConfirmationCodeTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+77074054407';

    private function makeUser(): User
    {
        $city = City::create(['name' => 'Алматы']);

        return User::create([
            'name' => 'Тест',
            'phone' => self::PHONE,
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => false,
            'city_id' => $city->id,
        ]);
    }

    /** Выдаём номеру несколько кодов подряд, как это делает повторная отправка. */
    private function issueCodes(User $user, string ...$codes): void
    {
        $repo = new PhoneConfirmationRepo();
        foreach ($codes as $code) {
            $repo->store($user, self::PHONE, $code);
        }
    }

    private function confirm(string $code)
    {
        return $this->postJson('/api/auth/confirm-code', [
            'phone' => self::PHONE,
            'code' => $code,
        ]);
    }

    public function test_latest_code_is_accepted(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '101010', '830218');

        $this->confirm('830218')->assertOk();
    }

    public function test_previous_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '101010', '830218');

        $this->confirm('101010')->assertStatus(400);
    }

    /** Код одноразовый: повторное подтверждение тем же кодом не проходит. */
    public function test_code_cannot_be_reused(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '830218');

        $this->confirm('830218')->assertOk();
        $this->confirm('830218')->assertStatus(404);
    }

    public function test_used_codes_are_removed_from_storage(): void
    {
        $user = $this->makeUser();
        $this->issueCodes($user, '101010', '830218');

        $this->confirm('830218')->assertOk();

        $this->assertSame(
            0,
            PhoneConfirmation::where('phone', self::PHONE)->count(),
            'после подтверждения у номера не должно остаться действующих кодов'
        );
    }
}
