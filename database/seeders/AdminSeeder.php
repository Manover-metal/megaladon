<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Создаёт администратора для входа в Backpack (/admin).
     * Логин — по email, доступ в панель гейтит CheckIfAdmin по role === 'admin'.
     *
     * Идемпотентно: повторный запуск обновляет существующего админа,
     * а не плодит дубликаты. Значения можно переопределить через .env.
     */
    public function run()
    {
        $email = env('ADMIN_EMAIL', 'admin@megaladon.kz');
        $password = env('ADMIN_PASSWORD', 'Admin12345');
        $phone = env('ADMIN_PHONE', '+70000000000');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'               => 'Admin',
                'phone'              => $phone,
                'city_id'            => City::value('id'),
                'role'               => 'admin',
                // Сырой пароль: модель User хеширует его в setPasswordAttribute.
                'password'           => $password,
                'is_phone_confirmed' => true,
            ]
        );
    }
}
