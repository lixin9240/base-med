<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareLinkDeleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('video_share_link', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '链接ID不能为空',
            'id.integer' => '链接ID必须为整数',
            'id.exists' => '链接不存在',
        ];
    }
}