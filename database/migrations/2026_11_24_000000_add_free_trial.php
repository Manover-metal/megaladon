<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Бесплатный пробный период через App Store / Google Play.
 * Сам период настраивается в консолях магазинов (introductory offer /
 * offer с бесплатной фазой); trial_months — только подпись в приложении.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedTinyInteger('trial_months')->nullable()->after('validity');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn('trial_months'));
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('is_trial'));
    }
};
