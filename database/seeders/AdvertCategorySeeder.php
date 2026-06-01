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
        $rows = array_map(fn($name) => ['name' => $name, 'parent_id' => 0, 'created_at' => $now, 'updated_at' => $now], $categories);

        DB::table('advert_categories')->insertOrIgnore($rows);
    }
}
