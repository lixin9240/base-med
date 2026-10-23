<?php

namespace App\Services\Gyz;

use App\Models\ExamAnswer;
use App\Models\ExamRecord;
use App\Models\SystemConfig;
use App\Models\Video;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 数据统计服务
 *
 * 只做业务逻辑：统计大盘、各视频答题情况、最容易答错的题、导出统计。
 */
class StatisticsService
{
    /**
     * 环节展示名
     */
    private const STAGE_LABELS = ['pretest' => '前测', 'posttest' => '后测'];

    /**
     * 2.5.1 统计大盘（4 张指标卡）
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $agg = ExamRecord::query()
            ->selectRaw('COUNT(*) AS total_times, ROUND(AVG(accuracy_rate)) AS avg_accuracy, SUM(result_status = ?) AS pass_times', ['pass'])
            ->first();

        $totalTimes = (int) ($agg->total_times ?? 0);

        return [
            'video_total' => Video::count(),
            'video_online' => Video::query()->where('status', 1)->count(),
            'answer_total_times' => $totalTimes,
            // 无人答题时为 null（口径同 2.4.1 accuracy_rate）
            'avg_accuracy' => $totalTimes > 0 ? (int) $agg->avg_accuracy : null,
            'pass_rate' => $totalTimes > 0 ? (int) round($agg->pass_times / $totalTimes * 100) : null,
            // 通过线取自 system_config，默认 60
            'pass_score' => $this->configValue('pass_score', 60),
        ];
    }

    /**
     * 2.5.2 各视频答题情况（全量数组，页面无分页/筛选）
     *
     * @return array<int, array<string, mixed>>
     */
    public function videoList(): array
    {
        $videos = Video::query()
            ->with('category:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $stats = ExamRecord::query()
            ->selectRaw('video_id, COUNT(*) AS answer_times, ROUND(AVG(accuracy_rate)) AS avg_accuracy, SUM(result_status = ?) AS pass_times', ['pass'])
            ->groupBy('video_id')
            ->get()
            ->keyBy('video_id');

        return $videos->map(function (Video $video) use ($stats) {
            $stat = $stats->get($video->id);

            return [
                'video_id' => (int) $video->id,
                'title' => $video->title,
                'category_name' => $video->category->name ?? null,
                'status' => (int) $video->status,
                'answer_times' => $stat ? (int) $stat->answer_times : 0,
                // 无人答题时为 null，前端显示「—」
                'avg_accuracy' => $stat ? (int) $stat->avg_accuracy : null,
                'pass_rate' => $stat ? (int) round($stat->pass_times / $stat->answer_times * 100) : null,
            ];
        })->values()->all();
    }

    /**
     * 2.5.3 最容易答错的题（按错误率从高到低排序）
     *
     * @return array<int, array<string, mixed>>
     */
    public function wrongQuestion(): array
    {
        $rows = ExamAnswer::query()
            ->join('exam_record', 'exam_answer.exam_record_id', '=', 'exam_record.id')
            ->join('question', 'exam_answer.question_id', '=', 'question.id')
            ->join('video', 'exam_record.video_id', '=', 'video.id')
            ->selectRaw('exam_answer.question_id, exam_answer.stage, question.stem, video.title AS video_title, COUNT(*) AS answer_times, SUM(exam_answer.is_correct = 0) AS wrong_times')
            ->groupBy('exam_answer.question_id', 'exam_answer.stage', 'question.stem', 'video.title')
            ->orderByRaw('SUM(exam_answer.is_correct = 0) / COUNT(*) DESC, COUNT(*) DESC')
            ->get();

        return $rows->map(fn ($row) => [
            'question_id' => (int) $row->question_id,
            'stem' => $row->stem,
            'video_title' => $row->video_title,
            'stage' => $row->stage,
            'answer_times' => (int) $row->answer_times,
            'wrong_rate' => (int) round($row->wrong_times / $row->answer_times * 100),
        ])->values()->all();
    }

    /**
     * 每个视频最容易答错的题（前测+后测合并，取错误率最高的一题）
     *
     * @return Collection<int, array{stem: string, wrong_rate: int}>  键为 video_id
     */
    private function mostWrongQuestionByVideo(): Collection
    {
        $rows = ExamAnswer::query()
            ->join('exam_record', 'exam_answer.exam_record_id', '=', 'exam_record.id')
            ->join('question', 'exam_answer.question_id', '=', 'question.id')
            ->selectRaw('exam_record.video_id, question.stem, COUNT(*) AS answer_times, SUM(exam_answer.is_correct = 0) AS wrong_times')
            ->groupBy('exam_record.video_id', 'question.stem')
            ->get();

        return $rows
            ->groupBy(fn ($row) => (int) $row->video_id)
            ->map(function (Collection $group) {
                $top = $group
                    ->sortByDesc(fn ($row) => (float) $row->wrong_times / (float) $row->answer_times)
                    ->first();

                return [
                    'stem' => (string) $top->stem,
                    'wrong_rate' => (int) round((float) $top->wrong_times / (float) $top->answer_times * 100),
                ];
            });
    }

    /**
     * 2.5.4 导出统计（Excel 文件流：大盘指标 + 各视频答题情况 + 最容易答错的题）
     */
    public function export(): StreamedResponse
    {
        $overview = $this->overview();
        $videoRows = $this->videoList();
        $wrongRows = $this->wrongQuestion();
        $mostWrongByVideo = $this->mostWrongQuestionByVideo();

        $spreadsheet = new Spreadsheet();

        // ① 统计大盘
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('统计大盘');

        $metricRows = [
            ['视频总数', $overview['video_total']],
            ['其中已上架', $overview['video_online']],
            ['答题次数', $overview['answer_total_times']],
            ['平均正确率(%)', $overview['avg_accuracy'] ?? '—'],
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

        // ② 各视频答题情况（含每视频最容易答错的题与汇总行）
        $videoSheet = $spreadsheet->createSheet();
        $videoSheet->setTitle('各视频答题情况');

        $headers = ['视频', '分类', '状态', '答题次数', '平均正确率(%)', '通过率(%)', '最容易答错题', '错误率(%)'];

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
            $videoSheet->setCellValue([5, $row], $videoRow['avg_accuracy'] ?? '—');
            $videoSheet->setCellValue([6, $row], $videoRow['pass_rate'] ?? '—');
            $videoSheet->setCellValue([7, $row], $mostWrong['stem'] ?? '—');
            $videoSheet->setCellValue([8, $row], $mostWrong['wrong_rate'] ?? '—');
            $row++;
        }

        // 汇总行：总答题次数、总平均正确率、总通过率
        $videoSheet->setCellValue([1, $row], '合计');
        $videoSheet->setCellValue([4, $row], $overview['answer_total_times']);
        $videoSheet->setCellValue([5, $row], $overview['avg_accuracy'] ?? '—');
        $videoSheet->setCellValue([6, $row], $overview['pass_rate'] ?? '—');

        foreach (range('A', 'H') as $column) {
            $videoSheet->getColumnDimension($column)->setWidth(18);
        }
        $videoSheet->getColumnDimension('G')->setWidth(40);

        // ③ 最容易答错的题
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
            $wrongSheet->setCellValue([3, $row], self::STAGE_LABELS[$wrongRow['stage']] ?? $wrongRow['stage']);
            $wrongSheet->setCellValue([4, $row], $wrongRow['answer_times']);
            $wrongSheet->setCellValue([5, $row], $wrongRow['wrong_rate']);
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $wrongSheet->getColumnDimension($column)->setWidth(24);
        }

        return $this->streamXlsx($spreadsheet, '数据统计_' . now()->format('Ymd_His') . '.xlsx');
    }

    /**
     * 读取 system_config 配置项（缺省时使用默认值）
     */
    private function configValue(string $key, int $default): int
    {
        $value = SystemConfig::query()->where('config_key', $key)->value('config_value');

        return $value === null ? $default : (int) $value;
    }

    /**
     * 输出 xlsx 文件流并销毁内存中的表格对象
     */
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
