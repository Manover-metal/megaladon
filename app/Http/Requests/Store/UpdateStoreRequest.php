<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
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
            'type_id' => ['nullable', 'integer', 'exists:company_types,id'],
            'name' => ['nullable', 'string', 'min:3'],
            'bin' => ['nullable', 'string', 'min:12', 'max:13'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'lat' => ['nullable', 'numeric'],
            'lon' => ['nullable', 'numeric'],
            'full_address' => ['nullable', 'string', 'max:255'],
            'contacts.*.type' => ['nullable', 'in:site,email,phone,home_phone', 'string'],
            'contacts.*.contact_name' => ['nullable', 'string', 'min:2', 'max:20'],
            'contacts.*.value' => ['nullable', 'string'],
        ];
    }
}
