<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·操作日志模块（operation_log，对应接口文档 2.11）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class OperationLogController extends Controller
{
    /**
     * 2.11.1 操作日志列表（分页）
     * GET /api/admin/operation-log/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'admin_id' => 1,
                    'admin_name' => '系统管理员',
                    'module' => 'video',
                    'action' => 'create',
                    'target_id' => 1,
                    'detail' => '{ "title": "心肺复苏基础操作" }',
                    'ip' => '127.0.0.1',
                    'created_at' => '2026-10-19 10:00:00',
                ],
            ],
            'total' => 1,
        ]);
    }
}
