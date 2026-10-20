<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRecordExportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video_id' => ['nullable', 'integer', Rule::exists('video', 'id')],
            'result_status' => ['nullable', Rule::in(['pass', 'fail'])],
        ];
    }

    public function messages(): array
    {
        return [
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'result_status.in' => '结果筛选值不合法',
        ];
    }
}