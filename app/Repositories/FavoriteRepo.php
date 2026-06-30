<?php

namespace App\Repositories;

use App\Models\Favorite;

class FavoriteRepo 
{
    public function indexMy(int $userId)
    {
        return Favorite::with('executor', 'order', 'executor.city')
            ->where('user_id', $userId)
            ->get();
    }

    public function store(array $data) : void
    {
        Favorite::create($data);
    }
}