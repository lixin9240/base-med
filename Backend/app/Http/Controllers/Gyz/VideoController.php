<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gyz\VideoCreateRequest;
use App\Http\Requests\Gyz\VideoDeleteRequest;
use App\Http\Requests\Gyz\VideoDetailRequest;
use App\Http\Requests\Gyz\VideoListRequest;
use App\Http\Requests\Gyz\VideoUpdateRequest;
use App\Http\Requests\Gyz\VideoUpdateStatusRequest;
use App\Http\Requests\Gyz\VideoUploadChunkRequest;
use App\Http\Requests\Gyz\VideoUploadCompleteRequest;
use App\Http\Requests\Gyz\VideoUploadCoverRequest;
use App\Http\Requests\Gyz\VideoUploadInitRequest;
use App\Services\Gyz\VideoService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 视频管理控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 VideoService。
 */
class VideoController extends Controller
{
    public function __construct(
        private readonly VideoService $videoService
    ) {
    }

    /**
     * 2.2.1 视频列表
     */
    public function list(VideoListRequest $request): JsonResponse
    {
        $data = $this->videoService->list($request->validated());

        return Result::success('获取成功', $data);
    }

    /**
     * 2.2.2 视频详情（编辑弹窗回填）
     */
    public function detail(VideoDetailRequest $request): JsonResponse
    {
        $data = $this->videoService->detail((int) $request->validated('id'));

        return Result::success('获取成功', $data);
    }

    /**
     * 2.2.3 新增视频（弹窗保存）
     */
    public function create(VideoCreateRequest $request): JsonResponse
    {
        $id = $this->videoService->create($request->validated());

        return Result::success('新增成功', ['id' => $id]);
    }

    /**
     * 2.2.4 编辑视频（弹窗保存）
     */
    public function update(VideoUpdateRequest $request): JsonResponse
    {
        $this->videoService->update($request->validated());

        return Result::success('保存成功', ['success' => true]);
    }

    /**
     * 2.2.5 上架/下架切换（列表页开关）
     */
    public function updateStatus(VideoUpdateStatusRequest $request): JsonResponse
    {
        $this->videoService->updateStatus(
            (int) $request->validated('id'),
            (int) $request->validated('status')
        );

        return Result::success('操作成功', ['success' => true]);
    }

    /**
     * 2.2.6 删除视频（软删除）
     */
    public function delete(VideoDeleteRequest $request): JsonResponse
    {
        $this->videoService->delete((int) $request->validated('id'));

        return Result::success('删除成功', ['success' => true]);
    }

    /**
     * 2.2.7 初始化视频上传（本地上传，source_type=2）
     */
    public function uploadInit(VideoUploadInitRequest $request): JsonResponse
    {
        $data = $this->videoService->uploadInit($request->validated());

        return Result::success('上传任务已初始化', $data);
    }

    /**
     * 上传封面图片（本地上传，source_type=2）
     */
    public function uploadCover(VideoUploadCoverRequest $request): JsonResponse
    {
        $data = $this->videoService->uploadCover($request->validated());

        return Result::success('封面上传成功', $data);
    }

    /**
     * 本地模拟辅助：上传单个分片
     *
     * 真实环境使用 OSS 直传时，分片由前端 SDK 直传 OSS，可跳过本接口。
     */
    public function uploadChunk(VideoUploadChunkRequest $request): JsonResponse
    {
        $this->videoService->uploadChunk($request->validated());

        return Result::success('分片上传成功', ['success' => true]);
    }

    /**
     * 2.2.8 完成视频上传
     */
    public function uploadComplete(VideoUploadCompleteRequest $request): JsonResponse
    {
        $data = $this->videoService->uploadComplete($request->validated());

        return Result::success('上传完成', $data);
    }
}
