<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class SubscriptionPresenter extends BasePresenter
{
    public function list()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'duration' => $this->validity,
            'price' => $this->price,
            // Подпись «N мес. бесплатно»; право на период проверяет магазин.
            'trial_months' => $this->trial_months,
            'apple_product_id' => $this->apple_product_id,
            'google_product_id' => $this->google_product_id,
        ];
    }
}