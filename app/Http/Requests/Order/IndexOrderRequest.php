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
            'sortBy' => ['nullable', 'string'],
            'desc' => ['nullable', 'boolean'],
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer']
        ];
    }
}
