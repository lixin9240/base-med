<?php

namespace App\Http\Requests\Lx;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareLinkCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', Rule::exists('video', 'id')],
            'org_id' => ['nullable', 'integer', Rule::exists('organization', 'id')],
            'expire_type' => ['required', Rule::in(['permanent', '7d', '30d', 'custom'])],
            'expire_at' => ['nullable','date',Rule::requiredIf(fn () => $this->input('expire_type') === 'custom'),],
        ];
    }

    public function messages(): array
    {
        return [
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.exists' => '视频不存在',
            'org_id.integer' => '机构ID必须为整数',
            'org_id.exists' => '机构不存在',
            'expire_type.required' => '时效类型不能为空',
            'expire_type.in' => '时效类型不合法',
            'expire_at.required' => '自定义时效须填写过期时间',
            'expire_at.date' => '过期时间格式不正确',
        ];
    }
}