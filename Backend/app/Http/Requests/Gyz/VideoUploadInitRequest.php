<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 初始化视频上传请求（本地上传，source_type=2）
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoUploadInitRequest extends FormRequest
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
            'file_name' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1'],
            'video_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'file_name.required' => '原始文件名不能为空',
            'file_name.max' => '文件名不能超过255字',
            'file_size.required' => '文件大小不能为空',
            'file_size.integer' => '文件大小必须为整数',
            'file_size.min' => '文件大小不合法',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.min' => '视频ID不合法',
        ];
    }
}
