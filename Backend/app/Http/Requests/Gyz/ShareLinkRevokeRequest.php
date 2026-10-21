<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 撤回生效链接请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class ShareLinkRevokeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.min' => '视频ID不合法',
        ];
    }
}