<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class AdvertPresenter extends BasePresenter
{
    public function list()
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => mb_strimwidth($this->description, 0, 128, '...'),
            'price'       => (float) $this->price,
            'type'        => $this->type ?? 'advert',
            'category'    => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'city'        => $this->city ? [
                'id'   => $this->city->id,
                'name' => $this->city->name,
            ] : null,
            'media'       => $this->presentCollections($this->media, MediaFilePresenter::class, 'list'),
            'created_at'  => $this->created_at,
        ];
    }

    public function info()
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'price'            => (float) $this->price,
            'type'             => $this->type ?? 'advert',
            'additional_phone' => $this->additional_phone,
            'category'         => $this->category ? [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'city'             => $this->city ? [
                'id'   => $this->city->id,
                'name' => $this->city->name,
            ] : null,
            'media'            => $this->presentCollections($this->media, MediaFilePresenter::class, 'list'),
            'user'             => $this->user ? (new UserPresenter($this->user))->shortAdvert() : null,
            'created_at'       => $this->created_at,
        ];
    }

    public function categories()
    {
        return [
            'id'    => $this->id,
            'title' => $this->name,
            'child' => $this->presentCollections($this->child, AdvertPresenter::class, 'categories'),
        ];
    }
}
