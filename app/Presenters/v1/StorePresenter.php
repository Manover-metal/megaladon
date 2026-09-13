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

    // Свой магазин в профиле: вдобавок к карточке — окончание подписки
    // (unix-время в секундах, null без активного инвойса), как у
    // ExecutorPresenter::edited(). В list()/detail() поля нет: каталогу это
    // стоило бы лишнего запроса на каждый магазин, а чужим дата ни к чему.
    public function edited()
    {
        $invoice = $this->activeInvoice();

        return array_merge($this->list(), [
            'subscription_expired_at' => is_null($invoice) ? null : strtotime($invoice->expired_at),
        ]);
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
            'photo_url' => ($this->user && $this->user->photo_url)
                ? url($this->user->photo_url)
                : null,
            'contacts' => $this->presentCollections($this->contacts, StoreContactsPresenter::class, 'info'),
            'prices' => $this->presentCollections($this->media, MediaFilePresenter::class, 'list'),
        ];
    }
}