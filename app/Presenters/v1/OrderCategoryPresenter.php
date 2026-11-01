<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class OrderCategoryPresenter extends BasePresenter
{
    public function list()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'child' => $this->presentCollections($this->child, OrderCategoryPresenter::class, 'list'),
        ];
    }

    public function executorList()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // Вышедшие сборки приложения читают услуги исполнителя из name.
            'name' => $this->title,
        ];
    }
}
