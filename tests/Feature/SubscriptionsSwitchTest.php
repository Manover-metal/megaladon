<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Notifications\FcmPushNotification;
use App\Services\v1\StorePaymentService;
use Illuminate\Support\Facades\Notification;
use App\Models\OrderCategory;
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\MakesSubscribers;
use Tests\TestCase;

/**
 * Временный выключатель подписок (настройка subscriptions_enabled). Выключен —
 * подписка нигде не требуется; включён — всё как раньше.
 */
class SubscriptionsSwitchTest extends TestCase
{
    use RefreshDatabase, MakesSubscribers;

    private function enable(bool $on): void
    {
        Setting::where('key', 'subscriptions_enabled')->update(['value' => $on ? '1' : '0']);
    }

    private function publishService($user)
    {
        $this->app['auth']->forgetGuards();
        return $this->withToken($user->createToken('api')->plainTextToken)->postJson('/api/services', [
            'title' => 'Лазерная резка',
            'description' => 'Режем лист до 20 мм',
            'category_id' => OrderCategory::create(['title' => 'Лазерная резка', 'parent_id' => 0])->id,
            'city_id' => $this->city()->id,
        ]);
    }

    public function test_after_migration_subscriptions_are_off(): void
    {
        $this->getJson('/api/app-config')->assertOk()->assertExactJson(['subscriptions_enabled' => false]);
        $this->enable(true);
        $this->getJson('/api/app-config')->assertExactJson(['subscriptions_enabled' => true]);
    }

    public function test_off_executor_without_subscription_has_full_access(): void
    {
        $user = $this->makeUser('+77001110000');
        $this->makeExecutor($user);

        $this->publishService($user)->assertOk();

        // Бессрочная дата: старые версии приложения открывают по ней отклики и контакты.
        $this->app['auth']->forgetGuards();
        $profile = $this->withToken($user->createToken('api')->plainTextToken)->getJson('/api/user/profile')->assertOk();
        $this->assertSame(strtotime(Subscription::UNLIMITED_UNTIL), $profile->json('user.executor.subscription_expired_at'));
    }

    public function test_off_store_without_subscription_is_visible(): void
    {
        $store = $this->makeStore($this->makeUser('+77002220000'));

        $this->getJson('/api/store')->assertOk()->assertJsonPath('list.0.id', $store->id);
        $this->getJson("/api/store/{$store->id}")->assertOk();
    }

    public function test_on_subscription_is_required_again(): void
    {
        $this->enable(true);
        $user = $this->makeUser('+77001110000');
        $this->makeExecutor($user);
        $store = $this->makeStore($this->makeUser('+77002220000'));

        $this->publishService($user)->assertStatus(402);
        $this->getJson("/api/store/{$store->id}")->assertNotFound();
        $this->getJson('/api/store')->assertOk()->assertJsonCount(0, 'list');

        $this->app['auth']->forgetGuards();
        $profile = $this->withToken($user->createToken('api')->plainTextToken)->getJson('/api/user/profile');
        $this->assertNull($profile->json('user.executor.subscription_expired_at'));
    }

    public function test_on_paid_subscription_still_works(): void
    {
        $this->enable(true);
        $user = $this->makeUser('+77001110000');
        $executor = $this->makeExecutor($user);
        $executor->invoices()->create([
            'subscription_id' => $this->makePlan(Subscription::EXECUTOR)->id,
            'status' => Invoice::STATUS_PAID,
            'expired_at' => now()->addMonth()->toDateString(),
        ]);

        $this->publishService($user)->assertOk();
    }

    // Старые версии приложения ещё показывают экран подписки: оплатить
    // бесплатный доступ им не даём — ни исполнителю, ни магазину.
    public function test_off_subscription_requests_are_rejected(): void
    {
        $executorUser = $this->makeUser('+77001110000');
        $this->makeExecutor($executorUser);
        $storeUser = $this->makeUser('+77002220000');
        $this->makeStore($storeUser);

        foreach ([[$executorUser, 'executor', Subscription::EXECUTOR], [$storeUser, 'store', Subscription::STORE]] as [$user, $path, $type]) {
            $this->app['auth']->forgetGuards();
            $this->withToken($user->createToken('api')->plainTextToken)
                ->postJson("/api/invoice/$path/create", ['subscription_id' => $this->makePlan($type)->id, 'payment_method' => 'apple', 'platform' => 'ios'])
                ->assertForbidden()
                ->assertJsonPath('message', 'Сейчас все функции доступны бесплатно');
        }
        $this->assertSame(0, Invoice::count());
    }

    // Продление у старого подписчика: оплата записывается, но пуша
    // «Подписка оплачена» нет — о подписках сейчас не сообщаем.
    public function test_off_renewal_is_recorded_without_push(): void
    {
        Notification::fake();
        $executor = $this->makeExecutor($this->makeUser('+77001110000'));
        $request = $executor->invoices()->create([
            'subscription_id' => $this->makePlan(Subscription::EXECUTOR)->id,
            'payment_method' => Invoice::METHOD_APPLE,
        ]);

        (new StorePaymentService())->paid($request->uuid, 'tx-1', now()->addMonth(), []);

        $this->assertSame(Invoice::STATUS_PAID, $request->fresh()->status);
        Notification::assertNothingSent();
    }

    // Флаг виден в Админке → Настройки и переключается оттуда: строку заводит
    // миграция, раздел показывает все настройки.
    public function test_admin_sees_and_toggles_flag(): void
    {
        $admin = $this->makeUser('+77009990000');
        $admin->forceFill(['role' => 'admin'])->save();
        $token = $admin->createToken('admin')->plainTextToken;

        $list = $this->withToken($token)->getJson('/api/admin/setting')->assertOk()->json();
        $row = collect($list['data'] ?? $list['list'] ?? $list['rows'] ?? [])->firstWhere('key', 'subscriptions_enabled');
        $this->assertNotNull($row, 'строки нет в списке настроек: ' . json_encode($list, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Подписки включены', $row['label']);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson("/api/admin/setting/{$row['id']}", ['value' => true])->assertOk();
        $this->getJson('/api/app-config')->assertExactJson(['subscriptions_enabled' => true]);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson("/api/admin/setting/{$row['id']}", ['value' => false])->assertOk();
        $this->getJson('/api/app-config')->assertExactJson(['subscriptions_enabled' => false]);
    }
}
