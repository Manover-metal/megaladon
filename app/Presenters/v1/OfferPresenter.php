<?php

namespace App\Presenters\v1;

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
        return [
            'id'=> $this->id,
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
