<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', Rule::in(['single', 'multiple', 'judge'])],
            'knowledge_point' => ['nullable', 'string', 'max:64'],
            'stem' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'page_size.integer' => '每页数量必须为整数',
            'page_size.min' => '每页数量不能小于1',
            'page_size.max' => '每页数量不能超过100',
            'type.in' => '题型筛选值不合法',
            'knowledge_point.max' => '知识点标签不能超过64字',
            'stem.max' => '题干关键词不能超过300字',
        ];
    }
}