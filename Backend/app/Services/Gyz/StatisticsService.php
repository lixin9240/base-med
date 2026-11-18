<?php

namespace App\Services\Gyz;

use App\Models\ExamAnswer;
use App\Models\ExamRecord;
use App\Models\SystemConfig;
use App\Models\Video;
use App\Models\VisitRecord;
use App\Services\Traits\FiltersByMedicalStaff;
use Illuminate\Support\Collection;

/**
 * 数据统计服务：大盘、各视频答题情况、最容易答错的题（支持医护筛选）
 */
class StatisticsService
{
    use FiltersByMedicalStaff;

    /**
     * 环节展示名
     */
    public const STAGE_LABELS = ['pretest' => '前测', 'posttest' => '后测', 'both' => '前测/后测'];

    /**
     * 2.6.1 统计大盘（指标卡）
     *
     * @return array<string, mixed>
     */
    public function overview(?int $isMedicalStaff = null): array
    {
        $query = ExamRecord::query();
        $this->applyMedicalStaffFilter($query, $isMedicalStaff);

        $agg = $query
            ->selectRaw('COUNT(*) AS total_times, ROUND(AVG(pretest_score)) AS pre_avg_accuracy, ROUND(AVG(posttest_score)) AS post_avg_accuracy, SUM(result_status = ?) AS pass_times', ['pass'])
            ->first();

        $totalTimes = (int) ($agg->total_times ?? 0);

        return [
            'video_total' => Video::count(),
            'video_online' => Video::query()->where('status', 1)->count(),
            'answer_total_times' => $totalTimes,
            'answer_entry_total' => $this->answeredEntryTotal(),
            'avg_accuracy' => $totalTimes > 0 ? (int) $agg->post_avg_accuracy : null,
            'pre_avg_accuracy' => $totalTimes > 0 ? (int) $agg->pre_avg_accuracy : null,
            'post_avg_accuracy' => $totalTimes > 0 ? (int) $agg->post_avg_accuracy : null,
            'pass_rate' => $totalTimes > 0 ? (int) round($agg->pass_times / $totalTimes * 100) : null,
            'pass_score' => $this->configValue('pass_score', 60),
            'is_medical_staff' => $isMedicalStaff,
        ];
    }

    /**
     * 公共端：进入答题页人数（visit_record 记录数，不受医护筛选影响）
     */
    public function answeredEntryTotal(): int
    {
        return VisitRecord::query()->count();
    }

    /**
     * 2.6.2 各视频答题情况
     *
     * @return array<int, array<string, mixed>>
     */
    public function videoList(?int $isMedicalStaff = null): array
    {
        $videos = Video::query()
            ->with('category:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $statsQuery = ExamRecord::query();
        $this->applyMedicalStaffFilter($statsQuery, $isMedicalStaff);

        $stats = $statsQuery
            ->selectRaw('video_id, COUNT(*) AS answer_times, ROUND(AVG(pretest_score)) AS pre_avg_accuracy, ROUND(AVG(posttest_score)) AS post_avg_accuracy, SUM(result_status = ?) AS pass_times', ['pass'])
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
                'avg_accuracy' => $stat ? (int) $stat->post_avg_accuracy : null,
                'pre_avg_accuracy' => $stat ? (int) $stat->pre_avg_accuracy : null,
                'post_avg_accuracy' => $stat ? (int) $stat->post_avg_accuracy : null,
                'pass_rate' => $stat ? (int) round($stat->pass_times / $stat->answer_times * 100) : null,
            ];
        })->values()->all();
    }

    /**
     * 2.6.3 最容易答错的题
     *
     * @return array<int, array<string, mixed>>
     */
    public function wrongQuestion(?int $isMedicalStaff = null): array
    {
        $query = ExamAnswer::query()
            ->join('exam_record', 'exam_answer.exam_record_id', '=', 'exam_record.id')
            ->join('question', 'exam_answer.question_id', '=', 'question.id')
            ->join('video', 'exam_record.video_id', '=', 'video.id');

        $this->applyMedicalStaffFilter($query, $isMedicalStaff, 'exam_record.is_medical_staff');

        $rows = $query
            ->selectRaw('exam_record.video_id, video.title AS video_title, exam_answer.question_id, question.stem, exam_answer.stage, COUNT(*) AS answer_times, SUM(exam_answer.is_correct = 0) AS wrong_times')
            ->groupBy('exam_record.video_id', 'video.title', 'exam_answer.question_id', 'question.stem', 'exam_answer.stage')
            ->get();

        $result = collect();

        foreach ($rows->groupBy(fn ($row) => (int) $row->video_id) as $videoRows) {
            $topPre = $this->topWrongQuestion($videoRows->where('stage', 'pretest'));
            $topPost = $this->topWrongQuestion($videoRows->where('stage', 'posttest'));

            if ($topPre !== null && $topPost !== null && (int) $topPre->question_id === (int) $topPost->question_id) {
                $result->push($this->mergeWrongQuestion($topPre, $topPost));
                continue;
            }

            if ($topPre !== null) {
                $result->push($this->toWrongQuestionRow($topPre));
            }

            if ($topPost !== null) {
                $result->push($this->toWrongQuestionRow($topPost));
            }
        }

        return $result
            ->sortByDesc(fn ($row) => [$row['wrong_rate'], $row['answer_times']])
            ->values()
            ->all();
    }

    /**
     * 每个视频最容易答错的题（导出「各视频答题情况」用）
     *
     * @return Collection<int, array{stem: string, wrong_rate: int}>
     */
    public function mostWrongQuestionByVideo(?int $isMedicalStaff = null): Collection
    {
        $query = ExamAnswer::query()
            ->join('exam_record', 'exam_answer.exam_record_id', '=', 'exam_record.id')
            ->join('question', 'exam_answer.question_id', '=', 'question.id');

        $this->applyMedicalStaffFilter($query, $isMedicalStaff, 'exam_record.is_medical_staff');

        $rows = $query
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

    private function topWrongQuestion(Collection $rows): ?object
    {
        return $rows
            ->sortByDesc(fn ($row) => (float) $row->wrong_times / (float) $row->answer_times)
            ->first();
    }

    private function toWrongQuestionRow(object $row, ?string $stage = null): array
    {
        return [
            'question_id' => (int) $row->question_id,
            'stem' => $row->stem,
            'video_title' => $row->video_title,
            'stage' => $stage ?? $row->stage,
            'answer_times' => (int) $row->answer_times,
            'wrong_rate' => (int) round((float) $row->wrong_times / (float) $row->answer_times * 100),
        ];
    }

    private function mergeWrongQuestion(object $pre, object $post): array
    {
        $answerTimes = (int) $pre->answer_times + (int) $post->answer_times;
        $wrongTimes = (int) $pre->wrong_times + (int) $post->wrong_times;

        return [
            'question_id' => (int) $pre->question_id,
            'stem' => $pre->stem,
            'video_title' => $pre->video_title,
            'stage' => 'both',
            'answer_times' => $answerTimes,
            'wrong_rate' => $answerTimes > 0 ? (int) round($wrongTimes / $answerTimes * 100) : 0,
        ];
    }

    public function configValue(string $key, int $default): int
    {
        $value = SystemConfig::query()->where('config_key', $key)->value('config_value');

        return $value === null ? $default : (int) $value;
    }
}
