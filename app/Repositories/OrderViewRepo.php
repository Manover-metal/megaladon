<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderView;

class OrderViewRepo
{
    // Запоминает, каким пользователь увидел заказ. Отсутствие строки трактуем
    // как «нового нет», поэтому строку надо заводить в тот момент, когда заказ
    // впервые попадает пользователю в списки, а не только при открытии.
    public function markSeen(int $userId, Order $order): void
    {
        OrderView::updateOrCreate(
            ['user_id' => $userId, 'order_id' => $order->id],
            [
                'seen_status' => $order->status,
                'seen_offers_count' => $order->offers()->count(),
            ]
        );
    }

    // Заказы пользователя, в которых с последнего просмотра сменился статус
    // или прибавились отклики.
    public function countMyWithUpdates(int $userId): int
    {
        return $this->joinViews($this->listed()->where('orders.user_id', $userId), $userId)
            ->where(function ($query) {
                $query->whereColumn('order_views.seen_status', '!=', 'orders.status')
                    ->orWhereRaw(
                        'order_views.seen_offers_count < ('
                        . 'select count(*) from order_offers where order_offers.order_id = orders.id'
                        . ')'
                    );
            })
            ->count();
    }

    // Заказы, где пользователь назначен исполнителем (именно их показывает
    // вкладка «мои отклики» — OrderService::indexMyResponded фильтрует по
    // executor_id). Чужие отклики исполнителя не касаются, поэтому здесь
    // смотрим только на статус.
    public function countRespondedWithUpdates(int $userId, int $executorId): int
    {
        return $this->joinViews($this->listed()->where('orders.executor_id', $executorId), $userId)
            ->whereColumn('order_views.seen_status', '!=', 'orders.status')
            ->count();
    }

    // Считаем только те заказы, что показывают списки «моих» — приложение
    // запрашивает их со статусами Order::LISTED_STATUSES. Заказ, ушедший в
    // архив (удалённый) или на модерацию, из списка пропадает, открыть его и
    // снять отметку нельзя — а в счётчике он висел навсегда: точка на
    // карточках гасла, число на вкладке оставалось.
    private function listed()
    {
        return Order::query()->whereIn('orders.status', Order::LISTED_STATUSES);
    }

    private function joinViews($query, int $userId)
    {
        return $query->join('order_views', function ($join) use ($userId) {
            $join->on('order_views.order_id', '=', 'orders.id')
                ->where('order_views.user_id', '=', $userId);
        });
    }
}
