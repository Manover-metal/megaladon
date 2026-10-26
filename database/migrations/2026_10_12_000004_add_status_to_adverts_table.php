<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Модерация объявлений, как у заказов: 1 — на проверке, 2 — активно,
    // 3 — отклонено. Default 2, чтобы уже опубликованные объявления не
    // пропали с площадки после миграции; новым AdvertService::create ставит 1.
    public function up(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(2)->after('type');
            $table->string('reject_reason')->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reject_reason']);
        });
    }
};
