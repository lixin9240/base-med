<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dr_id' => ['required', 'string', 'max:32', Rule::exists('visit_record', 'dr_id')],
            'video_id' => ['nullable', 'integer', Rule::exists('video', 'id'), 'required_without:exam_record_id'],
            'exam_record_id' => ['nullable', 'integer', Rule::exists('exam_record', 'id'), 'required_without:video_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'dr_id.required' => '匿名编号不能为空',
            'dr_id.exists' => '匿名编号不存在',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'video_id.required_without' => '视频ID与答题记录ID至少传一个',
            'exam_record_id.integer' => '答题记录ID必须为整数',
            'exam_record_id.exists' => '答题记录不存在',
            'exam_record_id.required_without' => '视频ID与答题记录ID至少传一个',
        ];
    }
}
