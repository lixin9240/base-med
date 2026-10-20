<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 操作日志列表请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class OperationLogListRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'module' => ['nullable', 'string', Rule::in(['video', 'question', 'exam', 'category', 'share'])],
            'action' => ['nullable', 'string', Rule::in(['create', 'update', 'delete', 'import', 'export', 'generate_link'])],
            'admin_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min' => '页码最小为1',
            'page_size.integer' => '每页条数必须为整数',
            'page_size.min' => '每页条数最小为1',
            'page_size.max' => '每页条数最大为100',
            'module.in' => '模块筛选不合法',
            'action.in' => '操作类型筛选不合法',
            'admin_id.integer' => '操作人ID必须为整数',
            'admin_id.min' => '操作人ID不合法',
        ];
    }
}
