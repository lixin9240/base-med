<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gyz\StatisticsFilterRequest;
use App\Services\Gyz\StatisticsExportService;
use App\Services\Gyz\StatisticsService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
        private readonly StatisticsExportService $statisticsExportService
    ) {
    }

    public function overview(StatisticsFilterRequest $request): JsonResponse
    {
        return Result::success('获取成功', $this->statisticsService->overview($request->medicalStaffFilter()));
    }

    public function videoList(StatisticsFilterRequest $request): JsonResponse
    {
        return Result::success('获取成功', $this->statisticsService->videoList($request->medicalStaffFilter()));
    }

    public function wrongQuestion(StatisticsFilterRequest $request): JsonResponse
    {
        return Result::success('获取成功', $this->statisticsService->wrongQuestion($request->medicalStaffFilter()));
    }

    public function export(StatisticsFilterRequest $request): StreamedResponse
    {
        return $this->statisticsExportService->export($request->medicalStaffFilter());
    }
}
