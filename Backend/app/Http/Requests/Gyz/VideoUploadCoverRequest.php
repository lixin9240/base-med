<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 封面上传请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoUploadCoverRequest extends FormRequest
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
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'file.required' => '请选择封面图片',
            'file.image' => '封面必须是图片文件',
            'file.mimes' => '封面只支持 jpg/png/webp/gif 格式',
            'file.max' => '封面图片不能超过 2MB',
        ];
    }
}
