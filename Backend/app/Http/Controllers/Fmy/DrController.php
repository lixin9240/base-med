<?php

namespace App\Http\Controllers\Fmy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmy\DrCreateRequest;
use App\Services\Fmy\DrService;
use Illuminate\Http\JsonResponse;

class DrController extends Controller
{
    public function __construct(
        private readonly DrService $drService
    ) {
    }

    /**
     * 获取匿名 DR 编号（3.1，点击视频进入时生成访问记录）
     */
    public function create(DrCreateRequest $request): JsonResponse
    {
        $data = $this->drService->create(
            (int) $request->validated('video_id')
        );

        return $this->success($data);
    }
}
