<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·题库管理模块（question 题目表，对应接口文档 2.9）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class QuestionController extends Controller
{
    /**
     * 2.9.1 题目列表（分页）
     * GET /api/admin/question/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 101,
                    'type' => 'single',
                    'stem' => '成人基础生命支持中，按压与通气的比例应为？',
                    'option_count' => 4,
                    'difficulty' => 2,
                    'knowledge_point' => '心肺复苏',
                    'created_at' => '2026-10-19 10:00:00',
                ],
            ],
            'total' => 30,
        ]);
    }

    /**
     * 2.9.2 编辑题目
     * POST /api/admin/question/update
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
     * 2.9.3 删除题目（软删除）
     * POST /api/admin/question/delete
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
