<?php

namespace App\Http\Requests\Service;

use App\Models\OrderCategory;
use App\Rules\LeafCategory;
use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Границы совпадают с decimal(15,2) в БД.
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'category_id' => ['required', 'integer', new LeafCategory(OrderCategory::class)],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'additional_phone' => ['nullable', 'string', 'starts_with:+'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'image'],
        ];
    }
}
