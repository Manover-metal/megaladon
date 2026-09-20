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
                'seen_updated_at' => $order->updated_at,
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

    // Заказы вкладки «как исполнитель»: назначенные мне и мои отклики, по
    // которым решения ещё нет. Набор берём из Order::scopeVisibleToExecutor —
    // тем же scope пользуется список (OrderRepo::index), иначе число на
    // вкладке разойдётся с её содержимым.
    //
    // Назначение отдельным условием не проверяем: при нём заказ переходит в
    // STATUS_HAS_EXECUTOR, и расхождение seen_status его уже ловит.
    public function countRespondedWithUpdates(int $userId, int $executorId): int
    {
        $query = $this->listed()->visibleToExecutor($userId, $executorId);

        return $this->joinViews($query, $userId)
            ->where(function ($q) {
                $q->whereColumn('order_views.seen_status', '!=', 'orders.status')
                    // Заказчик поправил заказ. Строки со старых релизов
                    // держат здесь null — их не считаем, иначе бейджи
                    // вспыхнут разом при первой же правке.
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('order_views.seen_updated_at')
                            ->whereColumn(
                                'order_views.seen_updated_at',
                                '<',
                                'orders.updated_at'
                            );
                    });
            })
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
