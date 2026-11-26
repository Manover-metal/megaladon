<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Избранные исполнители убраны из продукта целиком. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('favorites');
    }

    public function down(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index();
            $table->bigInteger('executor_id')->index();
            $table->bigInteger('order_id')->index();
            $table->timestamps();
        });
    }
};
