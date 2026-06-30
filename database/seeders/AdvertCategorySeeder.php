<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdvertCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Недвижимость',
            'Транспорт',
            'Электроника',
            'Работа',
            'Услуги',
        ];

        $now = now();

        // Каноничная таблица категорий объявлений — ad_categories (модель
        // AdCategory, FK adverts.category_id, валидация exists:ad_categories).
        // Старая advert_categories больше не используется.
        // updateOrInsert — идемпотентно: повторный запуск не плодит дубликаты.
        foreach ($categories as $name) {
            DB::table('ad_categories')->updateOrInsert(
                ['name' => $name, 'parent_id' => 0],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
