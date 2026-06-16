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
        $query = Store::with(['type', 'city', 'contacts', 'media']);
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
        if (isset($params['name'])) {
            $query->where('name', $params['name']);
        }

        if (isset($params['type_id'])) {
            $query->where('type_id', $params['type_id']);
        }

        if (isset($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
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
        $desc = isset($params['desc']) ? $params['desc'] : false;

        if (isset($params['sortBy'])) {
            $query->orderBy($params['sortBy'], $desc);
        }

        return $query;
    }
}