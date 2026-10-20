<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 获取前/后测题目列表请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoQuestionListRequest extends FormRequest
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
        ];
    }
}
