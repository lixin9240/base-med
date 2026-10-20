<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·分类管理模块（对应接口文档 2.7）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class CategoryController extends Controller
{
    /**
     * 2.7.1 分类列表（全量，不分页，下拉用）
     * GET /api/admin/category/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            [
                'id' => 1,
                'name' => '急救技能',
                'icon' => '',
                'sort_order' => 1,
                'status' => 1,
            ],
        ]);
    }

    /**
     * 2.7.2 新增分类
     * POST /api/admin/category/create
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function create(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.7.3 编辑分类
     * POST /api/admin/category/update
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function update(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.7.4 删除分类（软删除）
     * POST /api/admin/category/delete
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function delete(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }
}
