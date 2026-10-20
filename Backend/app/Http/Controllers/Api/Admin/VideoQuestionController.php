<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·题目配置模块（对应接口文档 2.3）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class VideoQuestionController extends Controller
{
    /**
     * 2.3.1 获取前/后测题目列表（抽屉 Tab）
     * GET /api/admin/video-question/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'question_id' => 101,
                    'type' => 'single',
                    'stem' => '成人基础生命支持中，按压与通气的比例应为？',
                    'options' => [
                        ['key' => 'A', 'content' => '15:2'],
                        ['key' => 'B', 'content' => '30:2'],
                    ],
                    'correct_answer' => 'B',
                    'analysis' => '成人基础生命支持推荐按压与通气比例为 30:2。',
                    'sort_order' => 1,
                ],
            ],
            'total' => 3,
        ]);
    }

    /**
     * 2.3.2 新增题目（弹窗保存）
     * POST /api/admin/video-question/add-question
     */
    public function addQuestion(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
        ]);
    }

    /**
     * 2.3.3 编辑题目（弹窗保存）
     * POST /api/admin/video-question/update-question
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function updateQuestion(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.3.4 下载模板（抽屉「下载模板」按钮）
     * GET /api/admin/video-question/download-template
     *
     * 说明：文档返回 excel 文件流，无 JSON data 结构；此处仅保留 JSON 响应模板占位。
     */
    public function downloadTemplate(): JsonResponse
    {
        return $this->success(data: null);
    }

    /**
     * 2.3.5 导入本组（抽屉「导入本组」/ 列表页「批量导入题目」按钮）
     * POST /api/admin/video/import-question
     */
    public function importQuestion(): JsonResponse
    {
        return $this->success(data: [
            'task_id' => 1,
            'task_no' => 'imp_20261019_001',
            'status' => 1,
        ]);
    }

    /**
     * 2.3.6 导出本组（抽屉「导出本组」按钮）
     * GET /api/admin/video-question/export
     *
     * 说明：文档返回 Excel 文件流，无 JSON data 结构；此处仅保留 JSON 响应模板占位。
     */
    public function export(): JsonResponse
    {
        return $this->success(data: null);
    }

    /**
     * 2.3.7 移除本组题目
     * POST /api/admin/video-question/remove
     *
     * 说明：文档未给出返回示例，按统一约定返回 success=true。
     */
    public function remove(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }
}
