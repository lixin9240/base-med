<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 完成视频上传请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoUploadCompleteRequest extends FormRequest
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
            'oss_upload_id' => ['required', 'string', 'max:128'],
            'video_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'oss_upload_id.required' => '上传任务ID不能为空',
            'oss_upload_id.max' => '上传任务ID不能超过128字',
            'video_id.required' => '视频ID不能为空',
            'video_id.integer' => '视频ID必须为整数',
            'video_id.min' => '视频ID不合法',
        ];
    }
}
