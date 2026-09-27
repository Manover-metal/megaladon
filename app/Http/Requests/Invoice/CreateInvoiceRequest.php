<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
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
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'payment_method' => ['nullable', 'in:apple,google,manual'],
            'platform' => ['nullable', 'in:ios,android'],
        ];
    }
}
