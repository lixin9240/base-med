<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserUpdateStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1', Rule::exists('admin_user', 'id')],
            'status' => ['required', 'integer', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '用户ID不能为空',
            'id.integer' => '用户ID必须为整数',
            'id.exists' => '用户不存在',
            'status.required' => '状态不能为空',
            'status.integer' => '状态必须为整数',
            'status.in' => '状态仅支持 0禁用 1正常',
        ];
    }
}
