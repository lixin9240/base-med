<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Services\Gyz\StatisticsService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 数据统计控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 StatisticsService。
 */
class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService
    ) {
    }

    /**
     * 2.5.1 统计大盘（4 张指标卡）
     */
    public function overview(): JsonResponse
    {
        $data = $this->statisticsService->overview();

        return Result::success('获取成功', $data);
    }

    /**
     * 2.5.2 各视频答题情况
     */
    public function videoList(): JsonResponse
    {
        $data = $this->statisticsService->videoList();

        return Result::success('获取成功', $data);
    }

    /**
     * 2.5.3 最容易答错的题
     */
    public function wrongQuestion(): JsonResponse
    {
        $data = $this->statisticsService->wrongQuestion();

        return Result::success('获取成功', $data);
    }

    /**
     * 2.5.4 导出统计（「导出统计」按钮）
     */
    public function export(): StreamedResponse
    {
        return $this->statisticsService->export();
    }
}
