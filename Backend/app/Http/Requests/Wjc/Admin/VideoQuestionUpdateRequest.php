<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 编辑题目请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoQuestionUpdateRequest extends FormRequest
{
    /**
     * 是否允许该请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 参数验证规则
     */
    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
            'stage' => ['required', Rule::in(['pretest', 'posttest'])],
            'question_id' => ['required', 'integer', Rule::exists('question', 'id')],
            'type' => ['required', Rule::in(['single', 'multiple', 'judge'])],
            'stem' => ['required', 'string', 'max:300'],
            'options' => ['array'],
            'options.*.key' => ['required', 'string', 'max:4'],
            'options.*.content' => ['required', 'string', 'max:200'],
            'correct_answer' => ['required', 'string', 'max:16'],
            'analysis' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'stage.required' => '测试阶段不能为空',
            'stage.in' => '测试阶段不合法',
            'question_id.required' => '题目ID不能为空',
            'question_id.integer' => '题目ID必须为整数',
            'question_id.exists' => '题目不存在',
            'type.required' => '题型不能为空',
            'type.in' => '题型不合法',
            'stem.required' => '题干不能为空',
            'stem.max' => '题干不能超过300字',
            'options.array' => '选项格式不正确',
            'options.*.key.required' => '选项标识不能为空',
            'options.*.content.required' => '选项内容不能为空',
            'options.*.content.max' => '选项内容不能超过200字',
            'correct_answer.required' => '正确答案不能为空',
            'correct_answer.max' => '正确答案不能超过16字',
            'analysis.max' => '答案解析不能超过500字',
        ];
    }
}
