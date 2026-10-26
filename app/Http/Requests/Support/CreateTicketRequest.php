<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
            'screenshots' => ['nullable', 'array', 'max:3'],
            'screenshots.*' => ['image', 'max:10240'],
        ];
    }
}
