<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 移除本组题目请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoQuestionRemoveRequest extends FormRequest
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
        ];
    }
}
