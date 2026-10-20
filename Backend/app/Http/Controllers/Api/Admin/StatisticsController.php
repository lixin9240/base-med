<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·数据统计模块（对应接口文档 2.6）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class StatisticsController extends Controller
{
    /**
     * 2.6.1 统计大盘（4 张指标卡）
     * GET /api/admin/statistics/overview
     */
    public function overview(): JsonResponse
    {
        return $this->success(data: [
            'video_total' => 4,
            'video_online' => 3,
            'answer_total_times' => 37,
            'avg_accuracy' => 70,
            'pass_rate' => 78,
            'pass_score' => 60,
        ]);
    }

    /**
     * 2.6.2 各视频答题情况（全量数组，无分页）
     * GET /api/admin/statistics/video-list
     */
    public function videoList(): JsonResponse
    {
        return $this->success(data: [
            [
                'video_id' => 1,
                'title' => '心肺复苏基础操作',
                'category_name' => '急救技能',
                'status' => 1,
                'answer_times' => 12,
                'avg_accuracy' => 64,
                'pass_rate' => 67,
            ],
            [
                'video_id' => 2,
                'title' => '高血压患者的日常管理',
                'category_name' => '慢病管理',
                'status' => 1,
                'answer_times' => 12,
                'avg_accuracy' => 67,
                'pass_rate' => 75,
            ],
            [
                'video_id' => 3,
                'title' => '胸片检查前的准备与配合',
                'category_name' => '影像检查',
                'status' => 1,
                'answer_times' => 13,
                'avg_accuracy' => 80,
                'pass_rate' => 92,
            ],
            [
                'video_id' => 4,
                'title' => '儿童疫苗接种后不良反应识别',
                'category_name' => '妇幼保健',
                'status' => 0,
                'answer_times' => 0,
                'avg_accuracy' => null,
                'pass_rate' => null,
            ],
        ]);
    }

    /**
     * 2.6.3 最容易答错的题（按错误率降序，全量数组）
     * GET /api/admin/statistics/wrong-question
     */
    public function wrongQuestion(): JsonResponse
    {
        return $this->success(data: [
            [
                'question_id' => 101,
                'stem' => '成人心肺复苏时，胸外按压的深度应为多少？',
                'video_title' => '心肺复苏基础操作',
                'stage' => 'pretest',
                'answer_times' => 12,
                'wrong_rate' => 58,
            ],
            [
                'question_id' => 205,
                'stem' => '胸片可以完全代替胸部 CT 用于所有肺部疾病的诊断。',
                'video_title' => '胸片检查前的准备与配合',
                'stage' => 'pretest',
                'answer_times' => 13,
                'wrong_rate' => 54,
            ],
        ]);
    }

    /**
     * 2.6.4 导出统计（「导出统计」按钮）
     * GET /api/admin/statistics/export
     *
     * 说明：文档返回 Excel 文件流，无 JSON data 结构；此处仅保留 JSON 响应模板占位。
     */
    public function export(): JsonResponse
    {
        return $this->success(data: null);
    }
}
