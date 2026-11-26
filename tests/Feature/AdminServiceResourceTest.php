<?php

namespace Tests\Feature;

use App\Admin\AdminError;
use App\Admin\Engine;
use App\Admin\Resources;
use App\Models\City;
use App\Models\OrderCategory;
use App\Models\Service;
use App\Models\User;
use App\Services\v1\PushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $status = Service::STATUS_MODERATE): Service
    {
        $u = User::create(['name' => 'U', 'phone' => '+77001234567', 'password' => 'secret123', 'is_phone_confirmed' => true,
            'city_id' => City::firstOrCreate(['name' => 'Алматы'])->id]);
        $cat = OrderCategory::create(['title' => 'Лазерная', 'parent_id' => 0]);
        $s = Service::create(['user_id' => $u->id, 'city_id' => $u->city_id, 'title' => 'Резка', 'status' => $status]);
        $s->categories()->sync([$cat->id]);
        return $s;
    }

    public function test_reject_with_empty_reason_sets_status_and_pushes(): void
    {
        $s = $this->service();
        $this->mock(PushService::class, fn ($m) => $m->shouldReceive('send')->once()
            ->withArgs(fn ($user, $title, $body, $data) => $data['type'] === 'service_rejected' && $data['service_id'] === (string) $s->id));

        Engine::for('service')->action('reject', $s->id, '', 1);

        $s->refresh();
        $this->assertSame(Service::STATUS_REJECTED, (int) $s->status);
        $this->assertNull($s->reject_reason);
    }

    public function test_approve_pushes_service_activated_and_badge_counts_moderation(): void
    {
        $s = $this->service();
        $this->assertSame(1, Resources::badges()['service']);
        $this->mock(PushService::class, fn ($m) => $m->shouldReceive('send')->once()
            ->withArgs(fn ($user, $title, $body, $data) => $data['type'] === 'service_activated'));

        Engine::for('service')->action('approve', $s->id, '', 1);

        $this->assertSame(Service::STATUS_ACTIVE, (int) $s->refresh()->status);
        $this->assertSame(0, Resources::badges()['service']);
    }

    public function test_order_category_with_services_cannot_be_deleted(): void
    {
        $s = $this->service();
        $this->expectException(AdminError::class);
        Engine::for('order-category')->remove($s->categories()->first()->id);
    }

    public function test_list_extra_labels_categories_per_row_and_remove_detaches(): void
    {
        $s = $this->service();
        $extra = Resources::get('service')['listExtra'](Service::whereKey($s->id)->get());
        $this->assertSame('Лазерная', $extra['categories'][(string) $s->id]);

        Engine::for('service')->remove($s->id);
        $this->assertSame(0, DB::table('service_categories')->where('service_id', $s->id)->count());
    }

    public function test_stats_include_services(): void
    {
        // stats() считает month(created_at) — функции MySQL; в sqlite подменяем.
        DB::connection()->getPdo()->sqliteCreateFunction('month', fn ($d) => (int) date('m', strtotime($d)));
        $this->assertContains('services', array_column(Resources::stats(), 'key'));
    }

    public function test_advert_resource_has_single_category_field_and_no_type(): void
    {
        $r = Resources::get('advert');
        $this->assertArrayHasKey('category_id', $r['fields']);
        $this->assertSame('multiselect', Resources::get('service')['fields']['categories']);
        $this->assertArrayNotHasKey('category_id', Resources::get('service')['fields']);
        $this->assertArrayNotHasKey('type', $r['fields']);
        $this->assertArrayNotHasKey('ad_category_id', $r['fields']);
        $this->assertArrayNotHasKey('service_category_id', $r['fields']);
    }

    public function test_admin_save_syncs_categories_and_rejects_non_leaf(): void
    {
        $s = $this->service();
        $root = OrderCategory::create(['title' => 'Резка', 'parent_id' => 0]);
        $leaf = OrderCategory::create(['title' => 'Плазма', 'parent_id' => $root->id]);
        $req = \Illuminate\Http\Request::create('/admin/service/' . $s->id, 'POST', [
            'user_id' => $s->user_id, 'title' => 'Резка', 'city_id' => $s->city_id, 'status' => Service::STATUS_ACTIVE,
            'categories' => [$leaf->id, $s->categories()->first()->id],
        ]);
        Engine::for('service')->save($s->id, $req);
        $this->assertCount(2, $s->categories()->get());
        $this->assertSame(['Лазерная', 'Плазма'], collect(Resources::get('service')['extra']($s->fresh())['categories'])->sort()->values()->all());

        $bad = \Illuminate\Http\Request::create('/admin/service/' . $s->id, 'POST', [
            'user_id' => $s->user_id, 'title' => 'Резка', 'city_id' => $s->city_id, 'status' => Service::STATUS_ACTIVE,
            'categories' => [$root->id],
        ]);
        $this->expectException(AdminError::class);
        Engine::for('service')->save($s->id, $bad);
    }
}
