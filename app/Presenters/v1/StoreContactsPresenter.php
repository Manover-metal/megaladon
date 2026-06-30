<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class StoreContactsPresenter extends BasePresenter
{
    public function info()
    {
        return [
            'type' => $this->type,
            'contact_name' => $this->contact_name ?? null,
            'value' => $this->value,
        ];
    }
}