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
     * 获取匿名 DR 编号（3.1）
     */
    public function create(DrCreateRequest $request): JsonResponse
    {
        $data = $this->drService->create(
            (string) $request->validated('token')
        );

        return $this->success($data);
    }
}
