<?php

namespace App\Http\Requests\Advert;

use Illuminate\Foundation\Http\FormRequest;

class IndexMyAdvertsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['nullable', 'string', 'in:advert,service'],
            'category' => ['nullable', 'integer'],
            'price_min' => ['nullable', 'numeric'],
            'price_max' => ['nullable', 'numeric'],
            'last' => ['nullable', 'string'],
            'city_id' => ['nullable', 'integer'],
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer']
        ];
    }
}
