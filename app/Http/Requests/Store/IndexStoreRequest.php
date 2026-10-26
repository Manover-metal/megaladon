<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class IndexStoreRequest extends FormRequest
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
            'rowsPerPage' => ['nullable', 'integer'],
            'startRow' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer', 'exists:company_types,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'rating_min' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => ['nullable', 'in:new,rating,name'],
        ];
    }
}
