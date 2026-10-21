<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 按状态筛选用户列表请求（2.2.6）
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class AdminUserFilterStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', Rule::in([0, 1])],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => '状态不能为空',
            'status.integer' => '状态必须为整数',
            'status.in' => '状态仅支持 0禁用 1正常',
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'page_size.integer' => '每页数量必须为整数',
            'page_size.min' => '每页数量不能小于1',
            'page_size.max' => '每页数量不能超过100',
        ];
    }
}
