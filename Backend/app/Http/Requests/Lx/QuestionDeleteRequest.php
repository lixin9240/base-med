<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('question', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '题目ID不能为空',
            'id.integer' => '题目ID必须为整数',
            'id.exists' => '题目不存在',
        ];
    }
}