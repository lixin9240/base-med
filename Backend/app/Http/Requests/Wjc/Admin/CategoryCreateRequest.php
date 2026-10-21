<?php

namespace App\Http\Requests\Wjc\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 新增分类请求
 *
 * 仅做参数验证，禁止任何数据库/日志/缓存操作。
 */
class CategoryCreateRequest extends FormRequest
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
            'name.required' => '分类名称不能为空',
            'name.max' => '分类名称不能超过32字',
            'icon.max' => '图标地址不能超过128字',
            'sort_order.integer' => '排序号必须为整数',
            'status.in' => '状态不合法',
        ];
    }
}
