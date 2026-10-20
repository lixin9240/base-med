<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopicUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('topic', 'id')],
            'name' => ['nullable', 'string', 'max:64'],
            'cover_url' => ['nullable', 'string', 'max:256'],
            'intro' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '课程ID不能为空',
            'id.integer' => '课程ID必须为整数',
            'id.exists' => '课程不存在',
            'name.max' => '课程名称不能超过64字',
            'cover_url.max' => '封面图URL不能超过256字',
            'intro.max' => '课程简介不能超过500字',
            'sort_order.integer' => '排序号必须为整数',
            'status.in' => '状态不合法',
        ];
    }
}