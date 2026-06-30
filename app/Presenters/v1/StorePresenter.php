<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class StorePresenter extends BasePresenter
{
    // Детальная карточка магазина и элемент списка отдают одинаковый набор
    // полей (включая rating), поэтому detail() делегирует в list().
    public function detail()
    {
        return $this->list();
    }

    public function list()
    {
        return [
            'id' => $this->id,
            'type' => !is_null($this->type) ? [
                'id' => $this->type->id,
                'name' => $this->type->name,
            ] : null,
            'name' => $this->name,
            'bin' => $this->bin,
            'rating' => $this->rating,
            'city' => !is_null($this->city) ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ] : null,
            'lat' => (double)$this->lat,
            'lon' => (double)$this->lon,
            'full_address' => $this->full_address,
            'photo_url' => $this->photo_url,
            'contacts' => $this->presentCollections($this->contacts, StoreContactsPresenter::class, 'info'),
            'prices' => $this->presentCollections($this->media, MediaFilePresenter::class, 'list'),
        ];
    }
}