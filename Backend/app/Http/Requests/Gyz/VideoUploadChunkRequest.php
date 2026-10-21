<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 分片上传请求（本地模拟辅助接口）
 *
 * 真实环境使用 OSS 直传时，分片由前端 SDK 上传，可跳过本接口。
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoUploadChunkRequest extends FormRequest
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
            'part_number' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file'],
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
            'part_number.required' => '分片序号不能为空',
            'part_number.integer' => '分片序号必须为整数',
            'part_number.min' => '分片序号不合法',
            'file.required' => '分片文件不能为空',
            'file.file' => '分片文件不合法',
        ];
    }
}
