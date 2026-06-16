<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'Ремонт и строительство',
            'Красота и здоровье',
            'Репетиторы и обучение',
            'Грузоперевозки',
            'Уборка и клининг',
            'IT и компьютерная помощь',
            'Авто-услуги',
            'Фото и видео',
            'Юридические услуги',
            'Бухгалтерские услуги',
            'Ремонт техники',
            'Праздники и мероприятия',
            'Сантехника и электрика',
            'Услуги няни и сиделки',
            'Дизайн и реклама',
        ];

        $now = now();

        foreach ($types as $name) {
            DB::table('service_types')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => $now, 'created_at' => $now, 'deleted_at' => null]
            );
        }
    }
}
