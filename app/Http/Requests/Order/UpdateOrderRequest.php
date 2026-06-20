<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:order_categories,id'],
            'price_recommended' => ['nullable'],
            'price_max' => ['nullable'],
            'execution_days' => ['required', 'integer', 'min:1'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file'],
        ];
    }
}
