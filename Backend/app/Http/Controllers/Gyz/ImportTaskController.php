<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gyz\ImportTaskConfirmRequest;
use App\Http\Requests\Gyz\ImportTaskDetailRequest;
use App\Http\Requests\Gyz\ImportTaskListRequest;
use App\Services\Gyz\ImportTaskService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 批量导入任务控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 ImportTaskService。
 */
class ImportTaskController extends Controller
{
    public function __construct(
        private readonly ImportTaskService $importTaskService
    ) {
    }

    /**
     * 2.8.1 任务列表
     */
    public function taskList(ImportTaskListRequest $request): JsonResponse
    {
        $data = $this->importTaskService->taskList($request->validated());

        return Result::success('获取成功', $data);
    }

    /**
     * 2.8.2 任务详情（校验结果与错误报告）
     */
    public function taskDetail(ImportTaskDetailRequest $request): JsonResponse
    {
        $data = $this->importTaskService->taskDetail((int) $request->validated('task_id'));

        return Result::success('获取成功', $data);
    }

    /**
     * 2.8.3 确认导入
     */
    public function confirm(ImportTaskConfirmRequest $request): JsonResponse
    {
        $this->importTaskService->confirm((int) $request->validated('task_id'));

        return Result::success('导入成功', ['success' => true]);
    }
}
