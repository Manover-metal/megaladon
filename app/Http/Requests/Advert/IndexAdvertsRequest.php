<?php

namespace App\Http\Requests\Advert;

use Illuminate\Foundation\Http\FormRequest;

class IndexAdvertsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
            'q' => ['nullable', 'string', 'max:100'],
            'with_photo' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:new,old,price_asc,price_desc'],
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer'],
            // Для публичной страницы пользователя. Обработка user_id
            // в AdvertRepo::applyFilterQuery уже есть — не хватало только
            // прохода через validated().
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
