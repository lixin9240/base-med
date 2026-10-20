<?php

namespace App\Http\Controllers\Fmy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmy\QuestionListRequest;
use App\Http\Requests\Fmy\VideoDetailRequest;
use App\Http\Requests\Fmy\VideoListRequest;
use App\Http\Requests\Fmy\VideoProgressRequest;
use App\Services\Fmy\VideoService;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    public function __construct(
        private readonly VideoService $videoService
    ) {
    }

    /**
     * 用户端首页视频列表（3.2）
     */
    public function list(VideoListRequest $request): JsonResponse
    {
        $data = $this->videoService->list(
            (string) $request->validated('dr_id')
        );

        return $this->success($data);
    }

    /**
     * 视频详情（观看视频页，3.3）
     */
    public function detail(VideoDetailRequest $request): JsonResponse
    {
        $data = $this->videoService->detail(
            (string) $request->validated('dr_id'),
            (int) $request->validated('video_id')
        );

        return $this->success($data);
    }

    /**
     * 上报视频观看进度（3.4）
     */
    public function progress(VideoProgressRequest $request): JsonResponse
    {
        $data = $this->videoService->progress(
            (string) $request->validated('dr_id'),
            (int) $request->validated('video_id'),
            (int) $request->validated('watched_seconds'),
            $request->validated('duration') !== null ? (int) $request->validated('duration') : null
        );

        return $this->success($data);
    }

    /**
     * 获取视频题目列表（学前/看完答题页共用，3.6）
     */
    public function questionList(QuestionListRequest $request): JsonResponse
    {
        $data = $this->videoService->questionList(
            (int) $request->validated('video_id')
        );

        return $this->success($data);
    }
}
