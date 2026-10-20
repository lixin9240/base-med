<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRecordListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'video_id' => ['nullable', 'integer', Rule::exists('video', 'id')],
            'result_status' => ['nullable', Rule::in(['pass', 'fail'])],
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
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'result_status.in' => '结果筛选值不合法',
        ];
    }
}