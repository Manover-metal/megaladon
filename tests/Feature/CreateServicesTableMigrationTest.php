<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateServicesTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_table_exists_and_adverts_has_no_type(): void
    {
        $this->assertTrue(Schema::hasTable('services'));
        $this->assertFalse(Schema::hasColumn('adverts', 'type'));
        $this->assertTrue(Schema::hasTable('service_categories'));
        foreach (['user_id', 'city_id', 'title', 'description', 'price', 'additional_phone', 'status', 'reject_reason', 'created_at'] as $col) {
            $this->assertTrue(Schema::hasColumn('services', $col), $col);
        }
    }

    public function test_up_deletes_leftover_services_with_media_and_reports(): void
    {
        // Откатываем до состояния «до услуг»: пока у adverts нет колонки type.
        for ($i = 0; $i < 10 && !Schema::hasColumn('adverts', 'type'); $i++) {
            Artisan::call('migrate:rollback', ['--step' => 1]);
        }
        $this->assertTrue(Schema::hasColumn('adverts', 'type'));

        $now = now();
        $base = ['user_id' => 1, 'city_id' => 1, 'title' => 't', 'category_id' => 1, 'created_at' => $now, 'updated_at' => $now];
        $ad = DB::table('adverts')->insertGetId($base + ['type' => 'advert']);
        $svc = DB::table('adverts')->insertGetId($base + ['type' => 'service']);
        foreach ([$ad, $svc] as $id) {
            DB::table('media_files')->insert(['storage_link' => "/x/$id.jpg", 'mediable_type' => 'App\\Models\\Advert', 'mediable_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('reports')->insert(['reporter_id' => 2, 'user_id' => 1, 'target_type' => 'advert', 'target_id' => $id, 'reason' => 'spam', 'created_at' => $now, 'updated_at' => $now]);
        }

        Artisan::call('migrate');

        $this->assertSame([$ad], DB::table('adverts')->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(1, DB::table('media_files')->where('mediable_type', 'App\\Models\\Advert')->count());
        $this->assertSame(1, DB::table('reports')->where('target_type', 'advert')->count());
        $this->assertSame(0, DB::table('services')->count());
    }
}
