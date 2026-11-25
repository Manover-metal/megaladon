<?php

namespace Tests\Feature;

use App\Models\AdCategory;
use App\Models\Invoice;
use App\Models\OrderCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    protected function setUp(): void
    {
        parent::setUp();
        // Подписки включены: без них любой исполнитель имеет полный доступ.
        Setting::where('key', 'subscriptions_enabled')->update(['value' => '1']);
    }

    private function as(User $user)
    {
        $this->app['auth']->forgetGuards();
        return $this->withToken($user->createToken('api')->plainTextToken);
    }

    private function paid($model): void
    {
        $plan = $this->makePlan($model instanceof \App\Models\Store ? Subscription::STORE : Subscription::EXECUTOR);
        $model->invoices()->create(['subscription_id' => $plan->id, 'status' => Invoice::STATUS_PAID, 'expired_at' => now()->addYear()->toDateString()]);
    }

    private function executorWithSubscription(string $phone = '+77001000001'): User
    {
        $u = $this->makeUser($phone);
        $this->paid($this->makeExecutor($u));
        return $u;
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Лазерная резка',
            'description' => 'Описание',
            'category_id' => OrderCategory::create(['title' => 'Лазерная резка', 'parent_id' => 0])->id,
            'city_id' => $this->city()->id,
        ], $extra);
    }

    public function test_executor_with_subscription_creates_service_in_moderation(): void
    {
        $u = $this->executorWithSubscription();
        $this->as($u)->postJson('/api/services', $this->payload())->assertOk();
        $s = Service::firstOrFail();
        $this->assertSame(Service::STATUS_MODERATE, (int) $s->status);
        $this->assertSame($u->id, (int) $s->user_id);
    }

    public function test_store_with_subscription_creates_service(): void
    {
        $u = $this->makeUser('+77001000002');
        $this->paid($this->makeStore($u));
        $this->as($u)->postJson('/api/services', $this->payload())->assertOk();
    }

    public function test_user_without_profiles_gets_403_and_executor_without_subscription_402(): void
    {
        $plain = $this->makeUser('+77001000003');
        $this->as($plain)->postJson('/api/services', $this->payload())->assertStatus(403);

        $noSub = $this->makeUser('+77001000004');
        $this->makeExecutor($noSub);
        $this->as($noSub)->postJson('/api/services', $this->payload())->assertStatus(402);
    }

    public function test_ad_category_id_is_rejected_for_service(): void
    {
        $u = $this->executorWithSubscription();
        // payload() заводит категорию услуг с id 1; берём id объявления, которого в дереве услуг нет.
        $payload = $this->payload();
        AdCategory::create(['name' => 'Станки']);
        $adCat = AdCategory::create(['name' => 'Инструмент']);
        $this->assertNull(OrderCategory::find($adCat->id), 'тест предполагает, что id есть только в дереве объявлений');
        $this->as($u)->postJson('/api/services', ['category_id' => $adCat->id] + $payload)->assertStatus(422);
    }

    public function test_public_index_shows_only_active_and_info_hides_moderated_from_strangers(): void
    {
        $author = $this->executorWithSubscription();
        $cat = OrderCategory::create(['title' => 'X', 'parent_id' => 0]);
        $base = ['user_id' => $author->id, 'city_id' => $author->city_id, 'title' => 't', 'category_id' => $cat->id];
        $active = Service::create($base + ['status' => Service::STATUS_ACTIVE]);
        $moderate = Service::create($base + ['status' => Service::STATUS_MODERATE]);

        $ids = collect($this->getJson('/api/services')->assertOk()->json('list'))->pluck('id')->all();
        $this->assertSame([$active->id], $ids);

        $this->getJson("/api/services/$moderate->id")->assertStatus(404);
        $this->as($author)->getJson("/api/services/$moderate->id")->assertOk()
            ->assertJsonPath('service.id', $moderate->id)->assertJsonMissingPath('service.type');
        $this->assertCount(2, $this->as($author)->getJson('/api/services/my')->assertOk()->json('list'));
    }

    public function test_foreign_update_and_delete_are_forbidden(): void
    {
        $author = $this->executorWithSubscription('+77001000005');
        $other = $this->executorWithSubscription('+77001000006');
        $s = Service::create(['user_id' => $author->id, 'city_id' => $author->city_id, 'title' => 't', 'category_id' => 1]);

        $this->as($other)->postJson("/api/services/$s->id/update", ['title' => 'x'])->assertStatus(403);
        $this->as($other)->deleteJson("/api/services/$s->id/delete")->assertStatus(403);
        $this->as($author)->deleteJson("/api/services/$s->id/delete")->assertOk();
        $this->assertNull(Service::find($s->id));
    }
}
