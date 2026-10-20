<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 管理员用户列表请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class AdminUserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'page_size.integer' => '每页数量必须为整数',
            'page_size.min' => '每页数量不能小于1',
            'page_size.max' => '每页数量不能超过100',
            'keyword.max' => '搜索关键词不能超过64字',
            'status.in' => '状态不合法',
        ];
    }
}