<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 下载题目模板请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoQuestionDownloadTemplateRequest extends FormRequest
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
            'stage' => ['required', Rule::in(['pretest', 'posttest'])],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'stage.required' => '测试阶段不能为空',
            'stage.in' => '测试阶段不合法',
        ];
    }
}
