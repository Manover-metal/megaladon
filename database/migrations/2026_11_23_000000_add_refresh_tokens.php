<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Короткий access-токен + refresh-токен с ротацией (см. TokenService).
 * Sanctum 2 не знает expires_at — срок проверяет колбэк в AppServiceProvider.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_used_at');
        });

        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique(); // sha256 от выданного значения
            // Без FK: протухшие access-токены можно чистить, не теряя refresh.
            $table->unsignedBigInteger('access_token_id')->nullable()->index();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Вечные токены приложения доживают 30 дней: новая версия сразу меняет
        // их на пару через /auth/upgrade-token, старая успеет обновиться.
        // Админские ('admin') не трогаем — у админки refresh нет.
        DB::table('personal_access_tokens')
            ->where('name', 'api')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addDays(30)]);
    }

    public function down()
    {
        Schema::dropIfExists('refresh_tokens');
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
