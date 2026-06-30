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
            'city' => !is_null($this->city) ? (new CityPresenter($this->city))->list() : null,
            'subscription_expired_at' => is_null($this->activeInvoice()) ? null : strtotime($this->activeInvoice()->expired_at),
        ];
    }

    public function short()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'city' => !is_null($this->city) ? (new CityPresenter($this->city))->list() : null,
            'rating' => $this->rating
        ];
    }
}