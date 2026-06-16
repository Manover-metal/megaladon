<?php

namespace App\Http\Requests\Advert;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdvertRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:advert,service'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'category_id' => ['required', 'integer', 'exists:advert_categories,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'additional_phone' => ['nullable', 'string', 'starts_with:+'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'image'],
        ];
    }
}
