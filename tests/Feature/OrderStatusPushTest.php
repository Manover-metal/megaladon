<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Executor;
use App\Models\Order;
use App\Models\User;
use App\Notifications\FcmPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Смена статуса заказа уведомляет исполнителя.
 *
 * Про завершение и снятие заказа исполнитель раньше не узнавал никак:
 * OrderService::complete() и delete() меняли статус напрямую через репозиторий,
 * не поднимая события, поэтому PushService не вызывался.
 *
 * Адресат всегда исполнитель: завершает и снимает заказ сам заказчик, для него
 * это не новость.
 */
class OrderStatusPushTest extends TestCase
{
    use RefreshDatabase;

    private ?City $city = null;

    /** Колонка users.city_id без дефолта — на MySQL нужен реальный город. */
    private function city(): City
    {
        return $this->city ??= City::create(['name' => 'Алматы']);
    }

    private function makeUser(string $phone, bool $pushEnabled = true): User
    {
        $user = User::create([
            'name' => 'User ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
            'city_id' => $this->city()->id,
        ]);

        // Не в $fillable: колонками управляет только само приложение.
        $user->forceFill([
            'push_notifications' => $pushEnabled,
            'device_token' => 'device-token-' . $phone,
        ])->save();

        return $user->refresh();
    }

    /**
     * @return array{0: User, 1: User, 2: Order} заказчик, пользователь-исполнитель, заказ
     */
    private function makeOrderWithExecutor(bool $executorPushEnabled = true): array
    {
        $customer = $this->makeUser('+77001110000');
        $executorUser = $this->makeUser('+77002220000', $executorPushEnabled);

        // lat/lon/full_address в схеме NOT NULL.
        $executor = Executor::create([
            'user_id' => $executorUser->id,
            'name' => 'Цех',
            'lat' => 43.238949,
            'lon' => 76.889709,
            'full_address' => 'Алматы, ул. Промышленная, 1',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'title' => 'Токарные работы',
            'category_id' => 1,
            'executor_id' => $executor->id,
            'status' => Order::STATUS_HAS_EXECUTOR,
        ]);

        return [$customer, $executorUser, $order];
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    public function test_executor_is_notified_when_order_is_completed(): void
    {
        [$customer, $executorUser, $order] = $this->makeOrderWithExecutor();
        Notification::fake();

        $this->withToken($this->tokenFor($customer))
            ->postJson("/api/order/{$order->id}/complete")
            ->assertOk();

        Notification::assertSentTo($executorUser, FcmPushNotification::class);
    }

    public function test_executor_is_notified_when_order_is_archived(): void
    {
        [$customer, $executorUser, $order] = $this->makeOrderWithExecutor();
        Notification::fake();

        $this->withToken($this->tokenFor($customer))
            ->deleteJson("/api/order/{$order->id}/delete")
            ->assertOk();

        Notification::assertSentTo($executorUser, FcmPushNotification::class);
    }

    public function test_customer_is_not_notified_about_own_action(): void
    {
        [$customer, , $order] = $this->makeOrderWithExecutor();
        Notification::fake();

        $this->withToken($this->tokenFor($customer))
            ->postJson("/api/order/{$order->id}/complete")
            ->assertOk();

        Notification::assertNotSentTo($customer, FcmPushNotification::class);
    }

    /**
     * Исполнитель не выбран — адресата нет. executor_id тут 0, а не null:
     * OrderService::create пишет именно 0, см. комментарий в модели Order.
     */
    public function test_no_push_when_order_has_no_executor(): void
    {
        $customer = $this->makeUser('+77001110000');
        $order = Order::create([
            'user_id' => $customer->id,
            'title' => 'Токарные работы',
            'category_id' => 1,
            'executor_id' => 0,
            'status' => Order::STATUS_ACTIVE,
        ]);
        Notification::fake();

        $this->withToken($this->tokenFor($customer))
            ->deleteJson("/api/order/{$order->id}/delete")
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_no_push_when_executor_disabled_notifications(): void
    {
        [$customer, $executorUser, $order] =
            $this->makeOrderWithExecutor(executorPushEnabled: false);
        Notification::fake();

        $this->withToken($this->tokenFor($customer))
            ->postJson("/api/order/{$order->id}/complete")
            ->assertOk();

        Notification::assertNotSentTo($executorUser, FcmPushNotification::class);
    }
}
