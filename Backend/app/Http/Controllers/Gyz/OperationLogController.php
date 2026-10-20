<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gyz\OperationLogListRequest;
use App\Services\Gyz\OperationLogService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 操作日志控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 OperationLogService。
 */
class OperationLogController extends Controller
{
    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 2.9.1 操作日志列表
     */
    public function list(OperationLogListRequest $request): JsonResponse
    {
        $data = $this->operationLogService->list($request->validated());

        return Result::success('获取成功', $data);
    }
}
