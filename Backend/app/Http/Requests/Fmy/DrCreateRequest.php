<?php

namespace App\Http\Requests\Fmy;

use Illuminate\Foundation\Http\FormRequest;

class DrCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => '访问令牌不能为空',
            'token.string' => '访问令牌格式不正确',
            'token.max' => '访问令牌长度不能超过64个字符',
        ];
    }
}
