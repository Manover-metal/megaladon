<?php

namespace App\Repositories;

use App\Models\Advert;
use Carbon\Carbon;

class AdvertRepo
{
    public function info(int $id)
    {
        return Advert::with('user', 'category', 'city', 'media')
            ->find($id);
    }

    public function index(array $params)
    {
        $query = Advert::with('media', 'category', 'city');
        $query = $this->applyFilterQuery($query, $params);           
        $query = $this->applyPaginationQuery($query, $params);
        $query = $this->applyOrderBy($query, $params);
        return $query->get();
    }

    public function store(array $data)
    {
        return Advert::create($data);
    }

    public function update(int $id, array $data)
    {
        return Advert::where('id', $id)
            ->update($data);
    }

    private function applyFilterQuery($query, $params)
    {
        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (isset($params['category'])) {
            $query->where('category_id', $params['category']);
        }

        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }

        if(isset($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
        }

        if(isset($params['price_min'])) {
            $query->where('price', '>', $params['price_min']);
        }

        if(isset($params['price_max'])) {
            $query->where('price', '<', $params['price_max']);
        }

        if (isset($params['last'])) {
            if ($params['last'] == 'last3day') {
                $query->where('created_at', '>', Carbon::now()->subDays(3)->toDateTimeString());
            }
            if ($params['last'] == 'last7day') {
                $query->where('created_at', '>', Carbon::now()->subDays(7)->toDateTimeString());
            }
            if ($params['last'] == 'last30day') {
                $query->where('created_at', '>', Carbon::now()->subDays(30)->toDateTimeString());
            }
        }

        if (isset($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        return $query;
    }

    private function applyPaginationQuery($query, $params)
    {
        if (isset($params['startRow'])) {
            $query->skip($params['startRow']);
        }
        if (isset($params['rowsPerPage'])) {
            $query->take($params['rowsPerPage']);
        } else {
            $query->take(100);
        }
        return $query;
    }

    private function applyOrderBy($query, $params)
    {
        $query->orderBy('id', 'DESC');
        return $query;
    }
}