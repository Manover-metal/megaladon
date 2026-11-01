<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Виды услуг сливаются с категориями заказов: услуги исполнителя теперь
 * ссылаются на order_categories. Старые категории (заказов и объявлений)
 * мягко удаляются — их заменяют новые сиды, а старые заказы и объявления
 * сохраняют category_id и видны в админке с прежним названием.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('ad_categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        $now = now();
        DB::table('order_categories')->whereNull('deleted_at')->update(['deleted_at' => $now]);
        DB::table('ad_categories')->update(['deleted_at' => $now]);
        // Объявления-услуги теперь берут категорию из order_categories: старый
        // id из ad_categories там означал бы чужую категорию. Без категории.
        DB::table('adverts')->where('type', 'service')->update(['category_id' => 0]);

        // В service_type_id вперемешку лежали id видов услуг (админка) и
        // категорий заказов (приложение) — сопоставить нельзя, очищаем.
        DB::table('executor_service_types')->delete();
        // sqlite (тесты) не умеет удалять и добавлять колонки в одном изменении.
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->dropColumn('service_type_id');
        });
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->bigInteger('order_category_id')->index();
        });

        Schema::dropIfExists('service_types');
    }

    public function down()
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('executor_service_types')->delete();
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->dropColumn('order_category_id');
        });
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->bigInteger('service_type_id');
        });

        Schema::table('ad_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
