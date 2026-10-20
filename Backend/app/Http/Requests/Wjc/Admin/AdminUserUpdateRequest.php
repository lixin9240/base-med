<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 编辑管理员请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('admin_user', 'id')],
            'username' => ['nullable', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:64'],
            'role' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', Rule::in([0, 1])],
            'password' => ['nullable', 'string', 'min:6', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '用户ID不能为空',
            'id.integer' => '用户ID必须为整数',
            'id.exists' => '用户不存在',
            'username.max' => '登录账号不能超过32字',
            'name.max' => '姓名不能超过32字',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱不能超过64字',
            'role.max' => '角色不能超过16字',
            'status.in' => '状态不合法',
            'password.min' => '密码不能少于6位',
            'password.max' => '密码不能超过32位',
        ];
    }
}