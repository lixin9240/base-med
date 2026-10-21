<?php

namespace App\Http\Controllers\Wjc\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wjc\Admin\VideoQuestionAddRequest;
use App\Http\Requests\Wjc\Admin\VideoQuestionDownloadTemplateRequest;
use App\Http\Requests\Wjc\Admin\VideoQuestionImportRequest;
use App\Http\Requests\Wjc\Admin\VideoQuestionListRequest;
use App\Http\Requests\Wjc\Admin\VideoQuestionRemoveRequest;
use App\Http\Requests\Wjc\Admin\VideoQuestionUpdateRequest;
use App\Services\Wjc\Admin\VideoQuestionService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 题目配置控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 VideoQuestionService。
 */
class VideoQuestionController extends Controller
{
    public function __construct(
        private readonly VideoQuestionService $videoQuestionService
    ) {
    }

    /**
     * 2.3.1 获取前/后测题目列表
     */
    public function list(VideoQuestionListRequest $request): JsonResponse
    {
        $data = $this->videoQuestionService->list($request->validated());

        return Result::success('获取成功', $data);
    }

    /**
     * 2.3.2 新增题目
     */
    public function addQuestion(VideoQuestionAddRequest $request): JsonResponse
    {
        $id = $this->videoQuestionService->add($request->validated());

        return Result::success('新增成功', ['id' => $id]);
    }

    /**
     * 2.3.3 编辑题目
     */
    public function updateQuestion(VideoQuestionUpdateRequest $request): JsonResponse
    {
        $id = $this->videoQuestionService->update($request->validated());

        return Result::success('保存成功', ['id' => $id]);
    }

    /**
     * 2.3.4 下载模板
     */
    public function downloadTemplate(VideoQuestionDownloadTemplateRequest $request): StreamedResponse
    {
        return $this->videoQuestionService->downloadTemplate($request->validated('stage'));
    }

    /**
     * 2.3.5 导入本组题目（创建导入任务）
     */
    public function importQuestion(VideoQuestionImportRequest $request): JsonResponse
    {
        $data = $this->videoQuestionService->import($request->validated());

        return Result::success('导入任务已创建', $data);
    }

    /**
     * 2.3.6 导出本组题目
     */
    public function export(VideoQuestionListRequest $request): StreamedResponse
    {
        return $this->videoQuestionService->export($request->validated());
    }

    /**
     * 2.3.7 移除本组题目
     */
    public function remove(VideoQuestionRemoveRequest $request): JsonResponse
    {
        $this->videoQuestionService->remove($request->validated());

        return Result::success('移除成功', ['success' => true]);
    }
}
