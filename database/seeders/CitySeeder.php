<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run()
    {
        $cities = [
            'Алматы',
            'Астана',
            'Шымкент',
            'Актобе',
            'Тараз',
            'Павлодар',
            'Усть-Каменогорск',
            'Семей',
            'Атырау',
            'Костанай',
            'Кызылорда',
            'Уральск',
            'Петропавловск',
            'Актау',
            'Темиртау',
            'Туркестан',
            'Кокшетау',
            'Талдыкорган',
            'Экибастуз',
            'Рудный',
        ];

        $now = now();
        $rows = array_map(fn($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now], $cities);

        DB::table('cities')->insertOrIgnore($rows);
    }
}
