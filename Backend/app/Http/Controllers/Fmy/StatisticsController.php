<?php

namespace App\Http\Controllers\Fmy;

use App\Http\Controllers\Controller;
use App\Services\Gyz\StatisticsService;
use Illuminate\Http\JsonResponse;

/**
 * 用户端统计控制器（只返回公开统计，不暴露管理端数据）
 */
class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService
    ) {
    }

    /**
     * 进入答题页人数（公开）
     */
    public function answeredEntry(): JsonResponse
    {
        return $this->success([
            'answer_entry_total' => $this->statisticsService->answeredEntryTotal(),
        ]);
    }
}
