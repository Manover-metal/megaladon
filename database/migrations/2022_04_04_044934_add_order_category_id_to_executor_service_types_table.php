<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderCategoryIdToExecutorServiceTypesTable extends Migration
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
            $table->dropColumn('service_type_id');
        });
        Schema::table('executor_service_types', function (Blueprint $table) {
            $table->bigInteger('order_category_id');
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
}
