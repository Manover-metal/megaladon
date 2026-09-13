<?php

namespace App\Http\Requests\Order;

use App\Models\OrderOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Не прислали (старые версии приложения) — база подставит total.
            'price_type' => ['nullable', Rule::in(OrderOffer::PRICE_TYPES)],
            'date' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'comment' => 'nullable|string',
            'expired_at' => 'nullable|date|date_format:Y-m-d',
        ];
    }
}
