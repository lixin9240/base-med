<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 发送重置验证码请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class ForgotPasswordRequest extends FormRequest
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
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'username.required' => '登录账号不能为空',
        ];
    }
}
