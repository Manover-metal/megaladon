<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Цена у объявления и услуги необязательна: продавец не всегда готов назвать
 * её сразу. Валидация (CreateAdvertRequest) это разрешала и раньше, но
 * колонка оставалась NOT NULL ещё с create_adverts_table, и запрос падал уже
 * в SQL: "Column 'price' cannot be null" — 500 вместо создания объявления.
 * Разрядность оставляем как в 2026_07_27 (decimal(15,2)): ->change() задаёт
 * определение колонки целиком, и без явных аргументов она схлопнулась бы
 * обратно в decimal(8,2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Объявления без цены уже существуют, и вернуть NOT NULL поверх них
        // нельзя — сначала проставляем 0.
        DB::table('adverts')->whereNull('price')->update(['price' => 0]);

        Schema::table('adverts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable(false)->change();
        });
    }
};
