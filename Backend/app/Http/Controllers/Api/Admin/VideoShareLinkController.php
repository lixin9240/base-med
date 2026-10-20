<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·访问链接管理模块（对应接口文档 2.4）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class VideoShareLinkController extends Controller
{
    /**
     * 2.4.1 生成访问链接
     * POST /api/admin/share-link/create
     */
    public function create(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
            'video_id' => 1,
            'token' => '8f3c9a2e5b7d',
            'url' => 'https://{domain}/h5/v?token=8f3c9a2e5b7d',
            'expire_type' => '7d',
            'expire_at' => '2026-10-26 10:00:00',
        ]);
    }

    /**
     * 2.4.2 链接列表（分页）
     * GET /api/admin/share-link/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'video_id' => 1,
                    'video_title' => '心肺复苏基础操作',
                    'org_id' => null,
                    'token' => '8f3c9a2e5b7d',
                    'url' => 'https://{domain}/h5/v?token=8f3c9a2e5b7d',
                    'expire_type' => '7d',
                    'expire_at' => '2026-10-26 10:00:00',
                    'visit_count' => 35,
                    'status' => 1,
                    'created_at' => '2026-10-19 10:00:00',
                ],
            ],
            'total' => 1,
            'page' => 1,
            'page_size' => 20,
        ]);
    }

    /**
     * 2.4.3 启用/失效链接
     * POST /api/admin/share-link/update-status
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function updateStatus(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.4.4 删除链接
     * POST /api/admin/share-link/delete
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function delete(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.4.5 访问记录列表（分页）
     * GET /api/admin/share-link/visit-record-list
     */
    public function visitRecordList(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'dr_id' => 'DR-2026-000017',
                    'share_link_id' => 1,
                    'video_title' => '心肺复苏基础操作',
                    'entered_at' => '2026-10-19 09:30:00',
                    'created_at' => '2026-10-19 09:30:00',
                ],
            ],
            'total' => 35,
        ]);
    }
}
