<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·答题记录模块（对应接口文档 2.5）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class ExamRecordController extends Controller
{
    /**
     * 2.5.1 答题记录列表（分页）
     * GET /api/admin/exam-record/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'submit_at' => '2026-10-18 10:34:00',
                    'video_id' => 1,
                    'video_title' => '心肺复苏基础操作',
                    'dr_id' => 'DR-2026-000003',
                    'pre_correct' => 2,
                    'pre_total' => 3,
                    'post_correct' => 3,
                    'post_total' => 3,
                    'accuracy_rate' => 100,
                    'result_status' => 'pass',
                ],
                [
                    'id' => 6,
                    'submit_at' => '2026-10-16 14:21:00',
                    'video_id' => 1,
                    'video_title' => '心肺复苏基础操作',
                    'dr_id' => 'DR-2026-000001',
                    'pre_correct' => 1,
                    'pre_total' => 3,
                    'post_correct' => 0,
                    'post_total' => 3,
                    'accuracy_rate' => 0,
                    'result_status' => 'fail',
                ],
            ],
            'total' => 12,
            'page' => 1,
            'page_size' => 20,
        ]);
    }

    /**
     * 2.5.2 查看作答详情（作答详情抽屉）
     * GET /api/admin/exam-record/detail
     */
    public function detail(): JsonResponse
    {
        return $this->success(data: [
            'exam_record_id' => 1,
            'video_title' => '心肺复苏基础操作',
            'dr_id' => 'DR-2026-000003',
            'submit_at' => '2026-10-18 10:34:00',
            'result_status' => 'pass',
            'accuracy_rate' => 100,
            'pre' => ['correct' => 2, 'total' => 3],
            'post' => ['correct' => 3, 'total' => 3],
            'list' => [
                [
                    'stage' => 'pretest',
                    'question_id' => 101,
                    'type' => 'single',
                    'stem' => '成人心肺复苏时，胸外按压的深度应为多少？',
                    'correct_answer' => 'C',
                    'user_answer' => null,
                    'is_correct' => 0,
                ],
                [
                    'stage' => 'pretest',
                    'question_id' => 102,
                    'type' => 'single',
                    'stem' => '成人胸外按压的频率应保持在每分钟多少次？',
                    'correct_answer' => 'C',
                    'user_answer' => 'C',
                    'is_correct' => 1,
                ],
                [
                    'stage' => 'posttest',
                    'question_id' => 105,
                    'type' => 'multiple',
                    'stem' => '下列哪些情形可以停止心肺复苏？',
                    'correct_answer' => 'A,B,C',
                    'user_answer' => 'A,B,C',
                    'is_correct' => 1,
                ],
            ],
        ]);
    }

    /**
     * 2.5.3 导出答题记录（列表页「导出」按钮）
     * GET /api/admin/exam-record/export
     *
     * 说明：文档返回 Excel 文件流，无 JSON data 结构；此处仅保留 JSON 响应模板占位。
     */
    public function export(): JsonResponse
    {
        return $this->success(data: null);
    }
}
