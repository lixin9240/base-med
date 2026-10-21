<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 新增管理员请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class AdminUserCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:6', 'max:32'],
            'name' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:64'],
            'role' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '登录账号不能为空',
            'username.max' => '登录账号不能超过32字',
            'password.required' => '密码不能为空',
            'password.min' => '密码不能少于6位',
            'password.max' => '密码不能超过32位',
            'name.required' => '姓名不能为空',
            'name.max' => '姓名不能超过32字',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱不能超过64字',
            'role.max' => '角色不能超过16字',
            'status.in' => '状态不合法',
        ];
    }
}