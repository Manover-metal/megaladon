<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('label');
            $table->timestamps();
        });

        // Включены: до релиза оплаты через магазины способ «через менеджера»
        // остаётся единственным.
        DB::table('settings')->insert([
            ['key' => 'manual_payment_ios', 'value' => '1', 'label' => 'Оплата через менеджера на iOS', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'manual_payment_android', 'value' => '1', 'label' => 'Оплата через менеджера на Android', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
