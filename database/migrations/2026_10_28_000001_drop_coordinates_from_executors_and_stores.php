<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Приложение больше не спрашивает геопозицию при регистрации и правке профиля. */
return new class extends Migration
{
    public function up()
    {
        foreach (['executors', 'stores'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['lat', 'lon']);
            });
        }
    }

    public function down()
    {
        foreach (['executors', 'stores'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('lat')->nullable();
                $table->decimal('lon')->nullable();
            });
        }
    }
};
