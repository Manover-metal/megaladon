<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('executors', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->foreignId('city_id')->nullable()->after('user_id');
        });
    }

    public function down()
    {
        //
    }
};
