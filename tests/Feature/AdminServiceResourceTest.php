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
        return Service::create(['user_id' => $u->id, 'city_id' => $u->city_id, 'title' => 'Резка', 'category_id' => $cat->id, 'status' => $status]);
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
        Engine::for('order-category')->remove($s->category_id);
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
        $this->assertArrayNotHasKey('type', $r['fields']);
        $this->assertArrayNotHasKey('ad_category_id', $r['fields']);
        $this->assertArrayNotHasKey('service_category_id', $r['fields']);
    }
}
