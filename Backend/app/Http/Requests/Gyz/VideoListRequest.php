<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 视频列表请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoListRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'keyword' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min' => '页码最小为1',
            'page_size.integer' => '每页条数必须为整数',
            'page_size.min' => '每页条数最小为1',
            'page_size.max' => '每页条数最大为100',
            'keyword.string' => '搜索关键词必须为字符串',
            'keyword.max' => '搜索关键词最长64个字符',
        ];
    }
}
