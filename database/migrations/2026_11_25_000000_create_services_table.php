<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Услуги исполнителей уезжают из adverts в свою таблицу. Данные не
 * переносятся: объявления и услуги удаляются перед выкладкой. Оставшиеся
 * строки type = service удаляем вместе с медиа и жалобами, иначе после
 * удаления колонки они превратились бы в объявления.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index();
            $table->bigInteger('city_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->bigInteger('category_id');
            $table->string('additional_phone')->nullable();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->string('reject_reason')->nullable();
            $table->timestamps();
        });

        $ids = DB::table('adverts')->where('type', 'service')->pluck('id')->all();
        if ($ids) {
            DB::table('media_files')->where('mediable_type', 'App\\Models\\Advert')->whereIn('mediable_id', $ids)->delete();
            DB::table('reports')->where('target_type', 'advert')->whereIn('target_id', $ids)->delete();
            DB::table('adverts')->whereIn('id', $ids)->delete();
        }

        Schema::table('adverts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->string('type')->default('advert')->after('category_id');
        });
        Schema::dropIfExists('services');
    }
};
