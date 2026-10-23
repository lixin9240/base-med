<?php

namespace App\Http\Controllers\Fmy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmy\ExamResultRequest;
use App\Http\Requests\Fmy\RetakeRequest;
use App\Http\Requests\Fmy\SubmitPostRequest;
use App\Http\Requests\Fmy\SubmitPreRequest;
use App\Services\Fmy\ExamService;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $examService
    ) {
    }

    /**
     * 提交学前答题（3.5）
     */
    public function submitPre(SubmitPreRequest $request): JsonResponse
    {
        $data = $this->examService->submitPre(
            (string) $request->validated('dr_id'),
            (int) $request->validated('video_id'),
            $request->validated('answers')
        );

        return $this->success($data);
    }

    /**
     * 提交看完答题（后测，3.7，需观看进度达到 90%）
     */
    public function submitPost(SubmitPostRequest $request): JsonResponse
    {
        $data = $this->examService->submitPost(
            (string) $request->validated('dr_id'),
            (int) $request->validated('video_id'),
            $request->validated('answers')
        );

        return $this->success($data);
    }

    /**
     * 再次答题（后测重答，3.9，需已完成一遍前测+后测）
     */
    public function retake(RetakeRequest $request): JsonResponse
    {
        $data = $this->examService->retake(
            (string) $request->validated('dr_id'),
            (int) $request->validated('video_id'),
            $request->validated('answers')
        );

        return $this->success($data);
    }

    /**
     * 获取答题结果（答题结果页，3.8）
     */
    public function result(ExamResultRequest $request): JsonResponse
    {
        $data = $this->examService->result(
            (string) $request->validated('dr_id'),
            $request->validated('video_id') !== null ? (int) $request->validated('video_id') : null,
            $request->validated('exam_record_id') !== null ? (int) $request->validated('exam_record_id') : null
        );

        return $this->success($data);
    }
}
