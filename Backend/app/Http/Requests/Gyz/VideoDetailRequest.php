<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 视频详情请求（编辑弹窗回填）
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoDetailRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'id.required' => '视频ID不能为空',
            'id.integer' => '视频ID必须为整数',
            'id.min' => '视频ID不合法',
        ];
    }
}
