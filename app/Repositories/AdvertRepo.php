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

        if(isset($params['city_id'])) {
            $query->where('city_id', $params['city_id']);
        }

        // Границы цены включительно: «от 1000» должно найти и ровно 1000.
        if(isset($params['price_min'])) {
            $query->where('price', '>=', $params['price_min']);
        }

        if(isset($params['price_max'])) {
            $query->where('price', '<=', $params['price_max']);
        }

        if (!empty($params['q'])) {
            $like = '%' . addcslashes($params['q'], '%_\\') . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)
                ->orWhere('description', 'like', $like));
        }

        if (!empty($params['with_photo'])) {
            $query->has('media');
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
        // Публичная лента — только прошедшие модерацию. В /adverts/my флага
        // нет: автор видит и те, что на проверке или отклонены.
        if (!empty($params['only_active'])) {
            $query->where('status', Advert::STATUS_ACTIVE);
        }

        // Объявления и услуги удалённых аккаунтов убираем с торговой площадки:
        // связаться с автором всё равно нельзя. Условие по deleted_at явное —
        // Advert::user() сейчас без withTrashed(), и глобальный скоуп сработал
        // бы сам, но добавь кто-нибудь withTrashed() (как в Order::user()) —
        // фильтр молча перестал бы отсекать удалённых.
        if (!empty($params['exclude_deleted_users'])) {
            $query->whereHas('user', fn ($q) => $q->whereNull('users.deleted_at'));
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
        switch ($params['sort'] ?? 'new') {
            case 'old':
                $query->orderBy('id');
                break;
            case 'price_asc':
                // Без цены («договорная») — в конец, а не в начало списка.
                $query->orderByRaw('price IS NULL')->orderBy('price')->orderBy('id', 'DESC');
                break;
            case 'price_desc':
                $query->orderBy('price', 'DESC')->orderBy('id', 'DESC');
                break;
            default:
                $query->orderBy('id', 'DESC');
        }
        return $query;
    }
}