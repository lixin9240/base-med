<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRecordDetailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'exam_record_id' => ['required', 'integer', Rule::exists('exam_record', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_record_id.required' => '答题记录ID不能为空',
            'exam_record_id.integer' => '答题记录ID必须为整数',
            'exam_record_id.exists' => '答题记录不存在',
        ];
    }
}