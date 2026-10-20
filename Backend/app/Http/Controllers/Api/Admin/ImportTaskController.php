<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·批量导入任务模块（对应接口文档 2.10）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class ImportTaskController extends Controller
{
    /**
     * 2.10.1 任务列表（分页）
     * GET /api/admin/import/task-list
     */
    public function taskList(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'task_no' => 'imp_20261019_001',
                    'file_name' => '题目.xlsx',
                    'total_rows' => 30,
                    'valid_rows' => 28,
                    'error_rows' => 2,
                    'status' => 4,
                    'created_at' => '2026-10-19 10:00:00',
                    'completed_at' => '2026-10-19 10:01:12',
                ],
            ],
            'total' => 1,
        ]);
    }

    /**
     * 2.10.2 任务详情（校验结果与错误报告）
     * GET /api/admin/import/task-detail
     */
    public function taskDetail(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
            'task_no' => 'imp_20261019_001',
            'file_name' => '题目.xlsx',
            'total_rows' => 30,
            'valid_rows' => 28,
            'error_rows' => 2,
            'status' => 2,
            'error_report' => [
                ['row' => 15, 'sheet' => '题目', 'message' => '正确答案格式错误'],
                ['row' => 22, 'sheet' => '题目', 'message' => '题干超过300字'],
            ],
            'created_at' => '2026-10-19 10:00:00',
        ]);
    }

    /**
     * 2.10.3 确认导入
     * POST /api/admin/import/confirm
     */
    public function confirm(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }
}
