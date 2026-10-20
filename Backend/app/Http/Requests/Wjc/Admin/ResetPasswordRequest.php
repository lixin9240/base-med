<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 重置密码请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class ResetPasswordRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'max:32'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'username.required' => '登录账号不能为空',
            'code.required' => '验证码不能为空',
            'code.size' => '验证码必须为6位数字',
            'password.required' => '新密码不能为空',
            'password.min' => '新密码不能少于6位',
            'password.max' => '新密码不能超过32位',
        ];
    }
}
