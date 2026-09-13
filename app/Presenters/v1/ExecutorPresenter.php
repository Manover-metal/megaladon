<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class ExecutorPresenter extends BasePresenter
{
    public function edited()
    {
        return [
            'id' => $this->id,
            'services' => $this->presentCollections($this->services, ServiceTypePresenter::class, 'list'),
            'name' => $this->name,
            'description' => $this->description,
            'bin' => $this->bin ?? null,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'full_address' => $this->full_address,
            'rating' => $this->rating ?? null,
            // Страница исполнителя показывает аватар и счётчик заказов —
            // до сих пор ни того, ни другого в этой форме не было, и экран
            // рисовал заглушку вместо фото. Оба поля живут у пользователя.
            'photo_url' => ($this->user && $this->user->photo_url)
                ? url($this->user->photo_url)
                : null,
            'count_orders' => $this->user ? $this->user->countCompletedOrders() : null,
            // Чат заводится по id пользователя, а не исполнителя.
            'user_id' => $this->user ? $this->user->id : null,
            'subscription_expired_at' => is_null($this->activeInvoice()) ? null : strtotime($this->activeInvoice()->expired_at),
        ];
    }

    public function short()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'rating' => $this->rating,
            'photo_url' => ($this->user && $this->user->photo_url)
                ? url($this->user->photo_url)
                : null,
            // Список исполнителей показывает, какие работы человек делает, —
            // без services карточка сообщала только имя и оценку.
            'services' => $this->presentCollections($this->services, ServiceTypePresenter::class, 'list'),
            'count_orders' => $this->user ? $this->user->countCompletedOrders() : null,
            'user_id' => $this->user ? $this->user->id : null,
        ];
    }
}