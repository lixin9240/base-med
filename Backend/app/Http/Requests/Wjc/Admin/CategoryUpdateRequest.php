<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 编辑分类请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class CategoryUpdateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:128'],
            'sort_order' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([0, 1])],
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
            'name.required' => '分类名称不能为空',
            'name.max' => '分类名称不能超过32字',
            'icon.max' => '图标地址不能超过128字',
            'sort_order.integer' => '排序号必须为整数',
            'status.in' => '状态不合法',
        ];
    }
}
