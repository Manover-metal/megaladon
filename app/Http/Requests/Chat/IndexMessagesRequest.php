<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class IndexMessagesRequest extends FormRequest
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
            'startRow' => ['nullable', 'integer'],
            'rowsPerPage' => ['nullable', 'integer'],
            'sortBy' => ['nullable', 'string'],
            'desc' => ['nullable', 'boolean'],
        ];
    }
}
