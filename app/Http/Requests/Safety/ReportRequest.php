<?php

namespace App\Http\Requests\Safety;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize()
    {
        return auth('api')->check();
    }

    public function rules()
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'target_type' => ['nullable', Rule::in(Report::TARGETS)],
            'target_id' => ['nullable', 'integer'],
            'reason' => ['required', Rule::in(Report::REASONS)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
