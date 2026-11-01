<?php

namespace App\Http\Requests\Advert;

use App\Models\Advert;
use App\Rules\LeafCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvertRequest extends FormRequest
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
            'type' => ['nullable', 'in:service,advert'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:advert,service'],
            'description' => ['nullable', 'string'],
            // Границы совпадают с decimal(15,2) в БД, см. CreateAdvertRequest.
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'category_id' => ['nullable', 'integer', new LeafCategory(Advert::categoryModel($this->input('type') ?? Advert::find($this->route('id'))?->type))],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'additional_phone' => ['nullable', 'string', 'starts_with:+'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'image'],
        ];
    }
}
