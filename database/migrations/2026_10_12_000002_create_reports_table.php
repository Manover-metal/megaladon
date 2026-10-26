<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Жалоба пользователя на другого пользователя (или его контент).
    // Решение принимает админ в админке: отклонить или забанить.
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('user_id'); // на кого жалуются
            $table->string('target_type', 20)->default('user'); // user|order|advert|message|store
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('reason', 20); // spam|abuse|inappropriate|fake|other
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('open'); // open|resolved
            $table->string('resolution', 20)->nullable(); // dismissed|banned
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
