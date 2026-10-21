<?php

namespace App\Services\Lx;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\ExamAnswer;
use App\Models\ExamRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExamRecordService
{
    /**
     * 环节展示顺序：前测在前，后测在后
     */
    private const STAGE_ORDER = ['pretest' => 0, 'posttest' => 1];

    /**
     * 答题记录列表
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $paginator = $this->baseQuery($params)
            ->orderByDesc('submit_at')
            ->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (ExamRecord $record) => [
                'id' => $record->id,
                'submit_at' => $record->submit_at?->format('Y-m-d H:i:s'),
                'video_id' => $record->video_id,
                'video_title' => $record->video->title ?? null,
                'dr_id' => $record->dr_id,
                'pre_correct' => $record->pretest_correct,
                'pre_total' => (int) $record->pre_total,
                'post_correct' => $record->posttest_correct,
                'post_total' => (int) $record->post_total,
                'accuracy_rate' => $record->accuracy_rate,
                'result_status' => $record->result_status,
            ])->values()
        );

        return $paginator;
    }

    /**
     * 作答详情
     *
     * @param  int  $examRecordId
     * @return array<string, mixed>
     */
    public function detail(int $examRecordId): array
    {
        $record = ExamRecord::with('video:id,title')->find($examRecordId);

        if (! $record) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '答题记录不存在');
        }

        $answers = ExamAnswer::with('question:id,type,stem,correct_answer')
            ->where('exam_record_id', $examRecordId)
            ->orderBy('id')
            ->get()
            ->sortBy(fn (ExamAnswer $answer) => self::STAGE_ORDER[$answer->stage] ?? 2);

        $preCorrect = 0;
        $preTotal = 0;
        $postCorrect = 0;
        $postTotal = 0;

        $list = $answers->map(function (ExamAnswer $answer) use (&$preCorrect, &$preTotal, &$postCorrect, &$postTotal) {
            $isCorrect = (int) $answer->is_correct;

            if ($answer->stage === 'pretest') {
                $preTotal++;
                $preCorrect += $isCorrect;
            } else {
                $postTotal++;
                $postCorrect += $isCorrect;
            }

            $question = $answer->question;

            return [
                'stage' => $answer->stage,
                'question_id' => $answer->question_id,
                'type' => $question->type ?? null,
                'stem' => $question->stem ?? '',
                'correct_answer' => $question->correct_answer ?? '',
                'user_answer' => $answer->user_answer,
                'is_correct' => $isCorrect,
            ];
        })->values()->all();

        return [
            'exam_record_id' => $record->id,
            'video_title' => $record->video->title ?? null,
            'dr_id' => $record->dr_id,
            'submit_at' => $record->submit_at?->format('Y-m-d H:i:s'),
            'result_status' => $record->result_status,
            'accuracy_rate' => $record->accuracy_rate,
            'pre' => ['correct' => $preCorrect, 'total' => $preTotal],
            'post' => ['correct' => $postCorrect, 'total' => $postTotal],
            'list' => $list,
        ];
    }

    /**
     * 导出答题记录为真正的 .xlsx 文件并上传阿里云 OSS
     *
     * @param  array<string, mixed>  $params
     * @return array{filename: string, url: string}
     */
    public function export(array $params): array
    {
        $records = $this->baseQuery($params)->orderByDesc('submit_at')->get();

        $headers = ['时间', '视频', '编号', '猜测', '看完答题', '正确率', '结果'];

        $rows = $records->map(function (ExamRecord $record) {
            return [
                $record->submit_at?->format('Y-m-d H:i:s') ?? '',
                $record->video->title ?? '',
                $record->dr_id ?? '',
                sprintf('%d/%d', $record->pretest_correct, (int) $record->pre_total),
                sprintf('%d/%d', $record->posttest_correct, (int) $record->post_total),
                sprintf('%d%%', (int) $record->accuracy_rate),
                $record->result_status === 'pass' ? '通过' : '未通过',
            ];
        })->values()->toArray();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray(array_merge([$headers], $rows), null, 'A1');

        $filename = 'exam_records_' . now()->format('Ymd_His') . '.xlsx';

        $tmpPath = $this->writeTempXlsx($spreadsheet);
        $ossPath = 'exam-records/' . $filename;

        try {
            Storage::disk('oss')->put($ossPath, file_get_contents($tmpPath));
        } finally {
            @unlink($tmpPath);
        }

        return [
            'filename' => $filename,
            'url' => Storage::disk('oss')->temporaryUrl($ossPath, now()->addDays(7)),
        ];
    }

    /**
     * 列表与导出共用的查询：视频标题、前后测题数统计、筛选条件
     *
     * @param  array<string, mixed>  $params
     * @return Builder
     */
    private function baseQuery(array $params): Builder
    {
        $query = ExamRecord::query()
            ->with('video:id,title')
            ->withCount([
                'examAnswers as pre_total' => fn ($q) => $q->where('stage', 'pretest'),
                'examAnswers as post_total' => fn ($q) => $q->where('stage', 'posttest'),
            ]);

        if (! empty($params['video_id'])) {
            $query->where('video_id', (int) $params['video_id']);
        }

        if (! empty($params['result_status'])) {
            $query->where('result_status', $params['result_status']);
        }

        return $query;
    }

    /**
     * 将 Spreadsheet 写入临时文件并返回文件路径
     */
    private function writeTempXlsx(Spreadsheet $spreadsheet): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'exam_records_');

        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }
}