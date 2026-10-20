<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 导入任务详情请求（校验结果与错误报告）
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class ImportTaskDetailRequest extends FormRequest
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
            'task_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'task_id.required' => '任务ID不能为空',
            'task_id.integer' => '任务ID必须为整数',
            'task_id.min' => '任务ID不合法',
        ];
    }
}
