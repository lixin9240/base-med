<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·视频管理模块（对应接口文档 2.2）
 *
 * 说明：本类为响应模板，仅演示 data 组织结构与统一响应封装；
 * 参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class VideoController extends Controller
{
    /**
     * 2.2.1 视频列表（分页）
     * GET /api/admin/video/list
     */
    public function list(): JsonResponse
    {
        return $this->success(data: [
            'list' => [
                [
                    'id' => 1,
                    'title' => '心肺复苏基础操作',
                    'category_name' => '急救技能',
                    'speaker' => '郭军',
                    'speaker_title' => '主任医师',
                    'pre_count' => 3,
                    'pre_total' => 3,
                    'post_count' => 3,
                    'post_total' => 3,
                    'status' => 1,
                    'transcode_status' => 2,
                    'answer_times' => 12,
                    'accuracy_rate' => 67,
                ],
                [
                    'id' => 4,
                    'title' => '儿童疫苗接种后不良反应识别',
                    'category_name' => '妇幼保健',
                    'speaker' => null,
                    'speaker_title' => null,
                    'pre_count' => 0,
                    'pre_total' => 3,
                    'post_count' => 0,
                    'post_total' => 3,
                    'status' => 0,
                    'transcode_status' => 2,
                    'answer_times' => 0,
                    'accuracy_rate' => null,
                ],
            ],
            'total' => 4,
            'page' => 1,
            'page_size' => 20,
        ]);
    }

    /**
     * 2.2.2 视频详情（编辑弹窗回填）
     * GET /api/admin/video/detail
     */
    public function detail(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
            'title' => '心肺复苏基础操作',
            'category_id' => 1,
            'category_name' => '急救技能',
            'speaker' => '郭军',
            'speaker_title' => '主任医师',
            'speaker_org' => '四川大学华西医院',
            'brief_intro' => '成人基础生命支持的规范动作：按压位置、深度、频率与通气比例，以及基层最常见的三个操作误区。',
            'source_type' => 1,
            'video_url' => '/videos/demo.mp4',
            'cover_url' => '',
            'text_key_points' => '【按压位置】...【按压深度与频率】...',
            'status' => 1,
            'sort_order' => 1,
            'duration' => 120,
            'transcode_status' => 2,
        ]);
    }

    /**
     * 2.2.3 新增视频（弹窗保存）
     * POST /api/admin/video/create
     */
    public function create(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
        ]);
    }

    /**
     * 2.2.4 编辑视频（弹窗保存）
     * POST /api/admin/video/update
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
     * 2.2.5 上架/下架切换（列表页开关）
     * POST /api/admin/video/update-status
     */
    public function updateStatus(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }

    /**
     * 2.2.6 删除视频（软删除）
     * POST /api/admin/video/delete
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
     * 2.2.7 初始化视频上传（本地上传，source_type=2）
     * POST /api/admin/video/upload/init
     */
    public function uploadInit(): JsonResponse
    {
        return $this->success(data: [
            'oss_upload_id' => 'upload_abc123',
            'oss_path' => 'video/2026/10/xxx.mp4',
            'part_size' => 1048576,
            'parts' => 10,
        ]);
    }

    /**
     * 2.2.8 完成视频上传
     * POST /api/admin/video/upload/complete
     */
    public function uploadComplete(): JsonResponse
    {
        return $this->success(data: [
            'video_id' => 1,
            'transcode_status' => 1,
        ]);
    }
}
