<?php

namespace App\Http\Requests\Gyz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 上架/下架切换请求（列表页开关）
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class VideoUpdateStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([0, 1])],
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
            'status.required' => '上架状态不能为空',
            'status.in' => '上架状态不合法',
        ];
    }
}
