<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // sqlite (тесты) не умеет удалять и добавлять колонки в одном изменении.
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->dropColumn('order_category_id');
        });
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->bigInteger('service_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('executor_service_types', function (Blueprint $table) {
            //
        });
    }
};
