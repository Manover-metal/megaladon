<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderOfferRequest extends FormRequest
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
            'price' => 'required|string|max:255',
            'date' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'comment' => 'nullable|string',
            'expired_at' => 'nullable|date|date_format:Y-m-d',
        ];
    }
}
