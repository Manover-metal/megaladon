<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class OrderPresenter extends BasePresenter
{
    public function list()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'count_offers' => $this->countOffers(),
            'city' => (new CityPresenter($this->city))->list(),
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
            'status' => $this->getStatusName(),
            'status_code' => $this->status,
            'execution_days' => $this->execution_days,
        ];
    }

    public function detail()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->getStatusName(),
            'status_code' => $this->status,
            'count_offers' => $this->countOffers(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'title' => $this->category->title, 
            ] : null,
            'price_max' => number_format($this->price_max, 2),
            'price_recommended' => number_format($this->price_recommended, 2),
            'execution_days' => $this->execution_days,
            'city' => (new CityPresenter($this->city))->list(),
            'user' => $this->user ? (new UserPresenter($this->user))->short() : null,
            'executor' => $this->executor ? (new ExecutorPresenter($this->executor))->short() : null,
            'files' => $this->media ? $this->presentCollections($this->media, MediaFilePresenter::class, 'list') : [],
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
        ];
    }

    public function short()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
        ];
    }
}