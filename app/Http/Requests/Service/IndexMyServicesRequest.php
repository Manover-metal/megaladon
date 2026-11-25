<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class IndexMyServicesRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            'category' => ['nullable', 'integer'],
            'price_min' => ['nullable', 'numeric'],
            'price_max' => ['nullable', 'numeric'],
            'last' => ['nullable', 'string'],
            'city_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'with_photo' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:new,old,price_asc,price_desc'],
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer'],
        ];
    }
}
