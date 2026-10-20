<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dr_id' => ['required', 'string', 'max:32', Rule::exists('visit_record', 'dr_id')],
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.user_answer' => ['required', 'string', 'max:32'],
            'answers.*.duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function messages(): array
    {
        return [
            'dr_id.required' => '匿名编号不能为空',
            'dr_id.exists' => '匿名编号不存在',
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'answers.required' => '答题内容不能为空',
            'answers.array' => '答题内容格式不正确',
            'answers.min' => '答题内容不能为空',
            'answers.*.question_id.required' => '题目ID不能为空',
            'answers.*.question_id.integer' => '题目ID必须为整数',
            'answers.*.user_answer.required' => '用户答案不能为空',
            'answers.*.user_answer.max' => '用户答案长度不能超过32个字符',
            'answers.*.duration.integer' => '答题用时必须为整数',
            'answers.*.duration.min' => '答题用时不能小于0',
        ];
    }
}
