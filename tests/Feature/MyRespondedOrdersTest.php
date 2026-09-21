<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Вкладка «как исполнитель»: GET /api/order/my-responded.
 *
 * Откликнуться на заказ может любой авторизованный пользователь —
 * OrderService::createOffer() профиля исполнителя не требует и пишет только
 * user_id. А indexMyResponded() этот профиль требовал и отвечал 404
 * «Executor not found», из-за чего свои же отклики посмотреть было нельзя.
 *
 * Набор вкладки описан в Order::scopeVisibleToExecutor: заказы, где я назначен
 * исполнителем, плюс заказы, где я откликнулся, а исполнитель ещё не выбран.
 * Без профиля исполнителя доступна только вторая половина — но именно она и
 * нужна такому пользователю.
 */
class MyRespondedOrdersTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private function city(): City
    {
        return $this->city ??= City::create(['name' => 'Алматы']);
    }

    private function makeUser(string $phone): User
    {
        return User::create([
            'name' => 'User ' . $phone,
            'phone' => $phone,
            'password' => Hash::make('secret123'),
            'is_phone_confirmed' => true,
            'city_id' => $this->city()->id,
        ]);
    }

    /** Заказ без выбранного исполнителя: OrderService::create пишет сюда 0. */
    private function makeOrder(User $customer, string $title): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'title' => $title,
            'category_id' => 1,
            'city_id' => $this->city()->id,
            'executor_id' => 0,
            'status' => Order::STATUS_ACTIVE,
        ]);
    }

    private function respond(User $user, Order $order): void
    {
        OrderOffer::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'city_id' => $this->city()->id,
            'price' => '10000',
            'date' => '2026-10-01',
            'comment' => 'Возьмусь',
        ]);
    }

    private function fetchResponded(User $user)
    {
        return $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/order/my-responded?startRow=0&rowsPerPage=15');
    }

    public function test_user_without_executor_profile_sees_own_responses(): void
    {
        $customer = $this->makeUser('+77001110000');
        $responder = $this->makeUser('+77002220000');
        $order = $this->makeOrder($customer, 'Токарные работы');
        $this->respond($responder, $order);

        $response = $this->fetchResponded($responder);

        $response->assertOk();
        $this->assertSame(
            [$order->id],
            array_column($response->json('list'), 'id'),
            'пользователь должен видеть заказ, на который откликнулся'
        );
    }

    /**
     * Защита от утечки: у заказов без исполнителя executor_id равен 0, и если
     * ноль передать в scope как идентификатор исполнителя, под условие попадут
     * вообще все такие заказы.
     */
    public function test_user_without_executor_profile_does_not_see_foreign_orders(): void
    {
        $customer = $this->makeUser('+77001110000');
        $responder = $this->makeUser('+77002220000');
        $mine = $this->makeOrder($customer, 'Мой отклик');
        $this->makeOrder($customer, 'Чужой заказ без отклика');
        $this->respond($responder, $mine);

        $response = $this->fetchResponded($responder);

        $response->assertOk();
        $this->assertSame(
            [$mine->id],
            array_column($response->json('list'), 'id'),
            'в выдачу не должны попадать заказы, на которые пользователь не откликался'
        );
    }

    public function test_empty_list_when_user_never_responded(): void
    {
        $customer = $this->makeUser('+77001110000');
        $stranger = $this->makeUser('+77002220000');
        $this->makeOrder($customer, 'Чужой заказ');

        $response = $this->fetchResponded($stranger);

        $response->assertOk();
        $this->assertSame([], $response->json('list'));
    }
}
