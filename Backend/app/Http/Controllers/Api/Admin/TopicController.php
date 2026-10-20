<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·课程管理模块（topic 课程表，对应接口文档 2.8）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class TopicController extends Controller
{
    /**
     * 2.8.1 课程列表（分页）
     * GET /api/admin/topic/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'name' => '基础急救',
                    'cover_url' => '',
                    'intro' => '课程简介',
                    'sort_order' => 1,
                    'status' => 1,
                    'video_count' => 4,
                    'created_at' => '2026-10-19 10:00:00',
                ],
            ],
            'total' => 1,
        ]);
    }

    /**
     * 2.8.2 新增课程
     * POST /api/admin/topic/create
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
     * 2.8.3 编辑课程
     * POST /api/admin/topic/update
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
     * 2.8.4 删除课程（软删除）
     * POST /api/admin/topic/delete
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
