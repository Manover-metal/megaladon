<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Каким пользователь последний раз видел заказ по времени правки.
     * Nullable и без бэкфилла: у существующих строк остаётся null, и это
     * значит «правок не показываем» — иначе после релиза бейдж «заказ
     * изменён» вспыхнул бы разом на всех старых заказах.
     */
    public function up(): void
    {
        Schema::table('order_views', function (Blueprint $table) {
            $table->timestamp('seen_updated_at')->nullable()->after('seen_offers_count');
        });
    }

    public function down(): void
    {
        Schema::table('order_views', function (Blueprint $table) {
            $table->dropColumn('seen_updated_at');
        });
    }
};
