<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('topic', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '课程ID不能为空',
            'id.integer' => '课程ID必须为整数',
            'id.exists' => '课程不存在',
        ];
    }
}