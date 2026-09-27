<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * uuid у старых инвойсов остаётся NULL: в магазин они уже не пойдут, а
     * уникальный индекс допускает несколько NULL.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('payment_method', ['apple', 'google', 'manual'])->default('manual');
            $table->uuid('uuid')->nullable()->unique();
            // Номер транзакции магазина — защита от повторной доставки вебхука.
            $table->string('store_transaction_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'uuid', 'store_transaction_id']);
        });
    }
};
