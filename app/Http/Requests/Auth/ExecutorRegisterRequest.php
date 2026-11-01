<?php

namespace App\Http\Requests\Auth;

use App\Models\OrderCategory;
use App\Rules\LeafCategory;
use Illuminate\Foundation\Http\FormRequest;

class ExecutorRegisterRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:3000'],
            'bin' => ['nullable', 'string', 'min:12', 'max:12'],
            'full_address' => ['required', 'string', 'max:255'],
            'services.*' => ['required', 'integer', new LeafCategory(OrderCategory::class)],
        ];
    }
}
