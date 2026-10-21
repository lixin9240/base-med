<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 删除管理员请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class AdminUserDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('admin_user', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '用户ID不能为空',
            'id.integer' => '用户ID必须为整数',
            'id.exists' => '用户不存在',
        ];
    }
}