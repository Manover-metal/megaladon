<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** У услуги несколько категорий из дерева услуг: связка вместо колонки. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('service_id')->index();
            $table->bigInteger('order_category_id')->index();
            $table->unique(['service_id', 'order_category_id']);
        });

        $rows = DB::table('services')->whereNotNull('category_id')->where('category_id', '>', 0)
            ->get(['id', 'category_id'])
            ->map(fn ($s) => ['service_id' => $s->id, 'order_category_id' => $s->category_id])
            ->all();
        if ($rows) {
            DB::table('service_categories')->insert($rows);
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->bigInteger('category_id')->default(0)->after('price');
        });
        foreach (DB::table('service_categories')->orderBy('id')->get() as $r) {
            DB::table('services')->where('id', $r->service_id)->update(['category_id' => $r->order_category_id]);
        }
        Schema::dropIfExists('service_categories');
    }
};
