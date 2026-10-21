<?php

namespace App\Http\Controllers\Lx;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lx\ExamRecordDetailRequest;
use App\Http\Requests\Lx\ExamRecordExportRequest;
use App\Http\Requests\Lx\ExamRecordListRequest;
use App\Services\Lx\ExamRecordService;
use Illuminate\Http\JsonResponse;

class ExamRecordController extends Controller
{
    public function __construct(
        private readonly ExamRecordService $examRecordService
    ) {
    }

    /**
     * 答题记录列表
     */
    public function list(ExamRecordListRequest $request): JsonResponse
    {
        $data = $this->examRecordService->list($request->validated());

        return $this->successPaginate($data);
    }

    /**
     * 作答详情
     */
    public function detail(ExamRecordDetailRequest $request): JsonResponse
    {
        $data = $this->examRecordService->detail(
            (int) $request->validated('exam_record_id')
        );

        return $this->success($data);
    }

    /**
     * 导出答题记录
     */
    public function export(ExamRecordExportRequest $request): JsonResponse
    {
        $data = $this->examRecordService->export($request->validated());

        return $this->success($data);
    }
}