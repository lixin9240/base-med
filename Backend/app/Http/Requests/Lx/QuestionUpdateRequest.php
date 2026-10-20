<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('question', 'id')],
            'type' => ['required', Rule::in(['single', 'multiple', 'judge'])],
            'stem' => ['required', 'string', 'max:300'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['required', 'string', 'max:16'],
            'analysis' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:256'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:3'],
            'knowledge_point' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '题目ID不能为空',
            'id.integer' => '题目ID必须为整数',
            'id.exists' => '题目不存在',
            'type.required' => '题型不能为空',
            'type.in' => '题型值不合法',
            'stem.required' => '题干不能为空',
            'stem.max' => '题干不能超过300字',
            'options.array' => '选项格式不正确',
            'correct_answer.required' => '正确答案不能为空',
            'correct_answer.max' => '正确答案不能超过16字',
            'analysis.max' => '答案解析不能超过500字',
            'image_url.max' => '题目配图URL不能超过256字',
            'difficulty.integer' => '难度必须为整数',
            'difficulty.min' => '难度不能小于1',
            'difficulty.max' => '难度不能大于3',
            'knowledge_point.max' => '知识点标签不能超过64字',
        ];
    }
}