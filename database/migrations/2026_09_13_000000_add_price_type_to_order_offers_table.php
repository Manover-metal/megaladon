<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceTypeToOrderOffersTable extends Migration
{
    /**
     * За что указана цена отклика: total — за всю работу, per_unit — за
     * штуку. По умолчанию total: все уже существующие отклики были ценой
     * за всю работу. Значения — константы OrderOffer::PRICE_TYPE_*.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_offers', function (Blueprint $table) {
            $table->enum('price_type', ['total', 'per_unit'])
                ->default('total')
                ->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_offers', function (Blueprint $table) {
            $table->dropColumn('price_type');
        });
    }
}
