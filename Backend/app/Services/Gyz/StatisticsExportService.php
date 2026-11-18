<?php

namespace App\Services\Gyz;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 数据统计导出（与查询 Service 拆分，控制单文件体量）
 */
class StatisticsExportService
{
    public function __construct(
        private readonly StatisticsService $statisticsService
    ) {
    }

    /**
     * 2.6.4 导出统计（Excel：大盘 + 各视频 + 易错题）
     */
    public function export(?int $isMedicalStaff = null): StreamedResponse
    {
        $overview = $this->statisticsService->overview($isMedicalStaff);
        $videoRows = $this->statisticsService->videoList($isMedicalStaff);
        $wrongRows = $this->statisticsService->wrongQuestion($isMedicalStaff);
        $mostWrongByVideo = $this->statisticsService->mostWrongQuestionByVideo($isMedicalStaff);

        $spreadsheet = new Spreadsheet();
        $this->fillOverviewSheet($spreadsheet, $overview, $isMedicalStaff);
        $this->fillVideoSheet($spreadsheet, $overview, $videoRows, $mostWrongByVideo);
        $this->fillWrongSheet($spreadsheet, $wrongRows);

        $suffix = $isMedicalStaff === 1 ? '_有医护' : ($isMedicalStaff === 0 ? '_无医护' : '');

        return $this->streamXlsx($spreadsheet, '数据统计' . $suffix . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    /**
     * @param  array<string, mixed>  $overview
     */
    private function fillOverviewSheet(Spreadsheet $spreadsheet, array $overview, ?int $isMedicalStaff): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('统计大盘');

        $scope = $isMedicalStaff === 1 ? '有医护人员' : ($isMedicalStaff === 0 ? '无医护人员' : '全部');

        $metricRows = [
            ['数据范围', $scope],
            ['视频总数', $overview['video_total']],
            ['其中已上架', $overview['video_online']],
            ['答题次数', $overview['answer_total_times']],
            ['答题前正确率(%)', $overview['pre_avg_accuracy'] ?? '—'],
            ['答题后正确率(%)', $overview['post_avg_accuracy'] ?? '—'],
            ['通过率(%)', $overview['pass_rate'] ?? '—'],
            ['通过线(%)', $overview['pass_score']],
        ];

        $row = 1;
        foreach ($metricRows as $metricRow) {
            $sheet->setCellValue([1, $row], $metricRow[0]);
            $sheet->setCellValue([2, $row], $metricRow[1]);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(16);
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  array<int, array<string, mixed>>  $videoRows
     * @param  \Illuminate\Support\Collection<int, array{stem: string, wrong_rate: int}>  $mostWrongByVideo
     */
    private function fillVideoSheet(Spreadsheet $spreadsheet, array $overview, array $videoRows, $mostWrongByVideo): void
    {
        $videoSheet = $spreadsheet->createSheet();
        $videoSheet->setTitle('各视频答题情况');

        $headers = ['视频', '分类', '状态', '答题次数', '答题前正确率(%)', '答题后正确率(%)', '通过率(%)', '最容易答错题', '错误率(%)'];
        foreach ($headers as $index => $header) {
            $videoSheet->setCellValue([$index + 1, 1], $header);
        }

        $row = 2;
        foreach ($videoRows as $videoRow) {
            $mostWrong = $mostWrongByVideo->get($videoRow['video_id']);
            $videoSheet->setCellValue([1, $row], $videoRow['title']);
            $videoSheet->setCellValue([2, $row], $videoRow['category_name'] ?? '');
            $videoSheet->setCellValue([3, $row], (int) $videoRow['status'] === 1 ? '已上架' : '已下架');
            $videoSheet->setCellValue([4, $row], $videoRow['answer_times']);
            $videoSheet->setCellValue([5, $row], $videoRow['pre_avg_accuracy'] ?? '—');
            $videoSheet->setCellValue([6, $row], $videoRow['post_avg_accuracy'] ?? '—');
            $videoSheet->setCellValue([7, $row], $videoRow['pass_rate'] ?? '—');
            $videoSheet->setCellValue([8, $row], $mostWrong['stem'] ?? '—');
            $videoSheet->setCellValue([9, $row], $mostWrong['wrong_rate'] ?? '—');
            $row++;
        }

        $videoSheet->setCellValue([1, $row], '合计');
        $videoSheet->setCellValue([4, $row], $overview['answer_total_times']);
        $videoSheet->setCellValue([5, $row], $overview['pre_avg_accuracy'] ?? '—');
        $videoSheet->setCellValue([6, $row], $overview['post_avg_accuracy'] ?? '—');
        $videoSheet->setCellValue([7, $row], $overview['pass_rate'] ?? '—');

        foreach (range('A', 'I') as $column) {
            $videoSheet->getColumnDimension($column)->setWidth(18);
        }
        $videoSheet->getColumnDimension('H')->setWidth(40);
    }

    /**
     * @param  array<int, array<string, mixed>>  $wrongRows
     */
    private function fillWrongSheet(Spreadsheet $spreadsheet, array $wrongRows): void
    {
        $wrongSheet = $spreadsheet->createSheet();
        $wrongSheet->setTitle('最容易答错的题');

        $headers = ['题干', '所属视频', '环节', '作答次数', '错误率(%)'];
        foreach ($headers as $index => $header) {
            $wrongSheet->setCellValue([$index + 1, 1], $header);
        }

        $row = 2;
        foreach ($wrongRows as $wrongRow) {
            $wrongSheet->setCellValue([1, $row], $wrongRow['stem']);
            $wrongSheet->setCellValue([2, $row], $wrongRow['video_title']);
            $wrongSheet->setCellValue([3, $row], StatisticsService::STAGE_LABELS[$wrongRow['stage']] ?? $wrongRow['stage']);
            $wrongSheet->setCellValue([4, $row], $wrongRow['answer_times']);
            $wrongSheet->setCellValue([5, $row], $wrongRow['wrong_rate']);
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $wrongSheet->getColumnDimension($column)->setWidth(24);
        }
    }

    private function streamXlsx(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($fileName) . '"',
        ]);
    }
}
