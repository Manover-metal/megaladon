<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PusherLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
            // 'secret_key' => ['required', 'string'],
        ];
    }
}