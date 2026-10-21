<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 生成 / 重新生成视频分享链接请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class ShareLinkGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer', 'min:1'],
            'expire_days' => ['required', 'integer', Rule::in([1, 3, 5, 7])],
        ];
    }

    public function messages(): array
    {
        return [
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.min' => '视频ID不合法',
            'expire_days.required' => '链接时效不能为空',
            'expire_days.integer' => '链接时效必须为整数',
            'expire_days.in' => '链接时效仅支持 1/3/5/7 天',
        ];
    }
}