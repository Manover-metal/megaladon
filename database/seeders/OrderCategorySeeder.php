<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderCategorySeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $parents = [
            'Строительство и ремонт',
            'Грузоперевозки и переезды',
            'Уборка',
            'Красота и здоровье',
            'IT и технологии',
            'Репетиторство',
            'Юридические услуги',
            'Фото и видео',
        ];

        foreach ($parents as $title) {
            DB::table('order_categories')->insertOrIgnore([
                'title' => $title,
                'parent_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $children = [
            'Строительство и ремонт' => ['Электрика', 'Сантехника', 'Отделка и покраска', 'Кровля'],
            'Грузоперевозки и переезды' => ['Переезд квартиры', 'Офисный переезд', 'Доставка грузов'],
            'Уборка' => ['Уборка квартиры', 'Уборка офиса', 'Химчистка мебели'],
            'Красота и здоровье' => ['Парикмахер', 'Маникюр и педикюр', 'Массаж'],
            'IT и технологии' => ['Ремонт компьютеров', 'Создание сайтов', 'Настройка оборудования'],
            'Репетиторство' => ['Математика', 'Английский язык', 'Подготовка к ЕГЭ'],
            'Юридические услуги' => ['Юридическая консультация', 'Составление договоров'],
            'Фото и видео' => ['Фотосъёмка', 'Видеосъёмка', 'Монтаж видео'],
        ];

        foreach ($children as $parentTitle => $childTitles) {
            $parentId = DB::table('order_categories')
                ->where('title', $parentTitle)
                ->value('id');

            if (!$parentId) continue;

            $rows = array_map(fn($title) => [
                'title' => $title,
                'parent_id' => $parentId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $childTitles);

            DB::table('order_categories')->insertOrIgnore($rows);
        }
    }
}
