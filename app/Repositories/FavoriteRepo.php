<?php

namespace App\Repositories;

use App\Models\Favorite;

class FavoriteRepo 
{
    public function indexMy(int $userId)
    {
        return Favorite::with('executor.user', 'executor.services', 'order')
            ->where('user_id', $userId)
            ->get();
    }

    // Один исполнитель — одна строка в «Моих исполнителях». Раньше каждое
    // нажатие «В избранное» создавало новую запись, и исполнитель в списке
    // повторялся. order_id — заказ, из которого добавили последним.
    public function store(array $data) : void
    {
        Favorite::updateOrCreate(
            ['user_id' => $data['user_id'], 'executor_id' => $data['executor_id']],
            ['order_id' => $data['order_id']]
        );
    }
}