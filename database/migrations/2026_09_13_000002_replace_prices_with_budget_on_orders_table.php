<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * У заказа остаётся один бюджет. Форма давно пишет только price_max (в
 * приложении это поле «Желаемый бюджет»), поэтому он и становится budget;
 * price_recommended приложение не присылает.
 */
class ReplacePricesWithBudgetOnOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('price_max', 'budget');
        });

        // Старые заказы, где была только рекомендованная цена, бюджет не теряют.
        DB::table('orders')
            ->whereNull('budget')
            ->whereNotNull('price_recommended')
            ->update(['budget' => DB::raw('price_recommended')]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('price_recommended');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('budget', 'price_max');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->double('price_recommended')->nullable()->after('description');
        });
    }
}
