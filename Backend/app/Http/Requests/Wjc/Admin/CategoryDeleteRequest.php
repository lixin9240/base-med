<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 删除分类请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class CategoryDeleteRequest extends FormRequest
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
            'id' => ['required', 'integer', Rule::exists('category', 'id')],
        ];
    }

    /**
     * 自定义错误提示
     */
    public function messages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer' => '分类ID必须为整数',
            'id.exists' => '分类不存在',
        ];
    }
}
