<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class IndexOrderRequest extends FormRequest
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
            'category' => ['nullable', 'integer', 'exists:order_categories,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'status' => ['nullable', 'integer'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['integer'],
            'last' => ['nullable', 'string'],
            // Белый список: значение уходит прямо в orderBy.
            'sortBy' => ['nullable', 'in:id,created_at,status,category_id,price_max,execution_days'],
            'q' => ['nullable', 'string', 'max:100'],
            'price_min' => ['nullable', 'numeric'],
            'price_max' => ['nullable', 'numeric'],
            'execution_days_max' => ['nullable', 'integer', 'min:1'],
            'without_offers' => ['nullable', 'boolean'],
            'desc' => ['nullable', 'boolean'],
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer'],
            // Для публичной страницы пользователя, см. IndexAdvertsRequest.
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
