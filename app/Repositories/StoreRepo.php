<?php 
namespace App\Repositories;

use App\Models\Store;

class StoreRepo
{
    public function store(array $data) : Store
    {
        return Store::create($data);
    }

    public function getByUserId($userId)
    {
        return Store::where('user_id', $userId)
            ->first();
    }

    public function update(int $id, array $data)
    {
        return Store::where('id', $id)
            ->update($data);
    }

    public function index(array $params)
    {
        $query = Store::with(['type', 'city', 'contacts', 'media', 'user']);
        $query = $this->applyFilter($query, $params);
        $query = $this->applyPagination($query, $params);
        $query = $this->applyOrderBy($query, $params);

        return $query->get();
    }

    public function count(array $params)
    {
        $query = Store::query();
        $query = $this->applyFilter($query, $params);

        return $query->count();
    }

    private function applyFilter($query, $params)
    {
        // Магазины без активной подписки в каталог не попадают.
        // Фильтр общий для index() и count(), чтобы счётчик и пагинация сходились.
        $query->hasActiveSubscription();

        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . addcslashes($params['name'], '%_\\') . '%');
        }

        if (isset($params['rating_min'])) {
            $query->where('rating', '>=', $params['rating_min']);
        }

        if (isset($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
        }

        // Скрываем магазин самого пользователя из списка металлопроката.
        if (isset($params['exclude_user_id'])) {
            $query->where('user_id', '!=', $params['exclude_user_id']);
        }

        return $query;
    }

    private function applyPagination($query, $params)
    {
        if (isset($params['startRow'])) {
            $query->offset($params['startRow']);
        }
        if (isset($params['rowsPerPage'])) {
            $query->limit($params['rowsPerPage']);
        } else {
            $query->limit(100);
        }
        return $query;
    }

    private function applyOrderBy($query, $params)
    {
        switch ($params['sort'] ?? 'new') {
            case 'rating':
                $query->orderBy('rating', 'DESC');
                break;
            case 'name':
                $query->orderBy('name');
                break;
        }
        $query->orderBy('id', 'DESC');

        return $query;
    }
}