<?php

namespace App\Presenters\v1;

use App\Models\Favorite;
use App\Models\OrderOffer;
use App\Presenters\BasePresenter;

class OfferPresenter extends BasePresenter
{
    public function list()
    {
        return $this->info();
    }

    // Комментарий хранится в колонке comment. Раньше здесь отдавался
    // $this->description — такого поля у отклика нет, и комментарий всегда
    // приходил null. description оставлен для старых версий приложения.
    public function info()
    {
        // В 'user' лежит пользователь (UserPresenter::short), а избранное
        // хранит исполнителя (favorites.executor_id). Приложение слало id
        // пользователя вместо исполнителя — и получало «Выбранное значение
        // поля executor id недопустимо». Отдаём id исполнителя отдельно.
        $executor = $this->user ? $this->user->executor : null;
        $viewerId = auth('api')->id();

        return [
            'id'=> $this->id,
            'executor_id' => $executor ? $executor->id : null,
            // Уже в избранном у смотрящего — сердечко на странице отклика
            // закрашено и повторно не нажимается.
            'is_favorite' => $executor && $viewerId
                ? Favorite::where('user_id', $viewerId)
                    ->where('executor_id', $executor->id)
                    ->exists()
                : false,
            'comment' => $this->comment,
            'description' => $this->comment,
            'date' => $this->date,
            'price' => $this->price,
            'price_type' => $this->price_type ?? OrderOffer::PRICE_TYPE_TOTAL,
            'expired_at' => $this->expired_at,
            'user' => (new UserPresenter($this->user))->short(),
            'city' => (new CityPresenter($this->city))->list(),
        ];
    }
}
