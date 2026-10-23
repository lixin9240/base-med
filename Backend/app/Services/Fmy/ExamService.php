<?php

namespace App\Services\Fmy;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\ExamAnswer;
use App\Models\ExamPretest;
use App\Models\ExamRecord;
use App\Models\SystemConfig;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Models\VideoQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamService
{
    public function __construct(
        private readonly VideoService $videoService
    ) {
    }

    /**
     * 提交学前答题：答案暂存到 exam_pretest 表，不生成答题记录
     *
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function submitPre(string $drId, int $videoId, array $answers): array
    {
        $visit = $this->videoService->resolveVisit($drId);
        $this->videoService->onShelfVideo($videoId);
        $this->videoService->assertVisitVideo($visit, $videoId);

        $questions = $this->videoService->questionSet($videoId);

        if ($questions->isEmpty()) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '该视频尚未配置题目');
        }

        $this->validateAnswers($questions, $answers);

        ExamPretest::updateOrCreate(
            [
                'user_id' => $visit->id,
                'video_id' => $videoId,
            ],
            [
                'dr_id' => $drId,
                'answers' => array_values($answers),
                'submitted_at' => now(),
            ]
        );

        return [
            'submitted' => 1,
            'pre_total' => $questions->count(),
            'pre_answered' => count($answers),
        ];
    }

    /**
     * 提交看完答题（后测）：校验观看进度门槛后，合并学前暂存答案写入完整答题记录
     *
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function submitPost(string $drId, int $videoId, array $answers): array
    {
        $visit = $this->videoService->resolveVisit($drId);
        $video = $this->videoService->onShelfVideo($videoId);
        $this->videoService->assertVisitVideo($visit, $video->id);

        $this->assertWatchProgressUnlocked($visit->id, $video);

        $pretest = ExamPretest::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $video->id)
            ->first();

        if (! $pretest) {
            throw new BusinessException(ResponseCode::STATUS_NOT_OPERABLE, '请先完成学前答题');
        }

        $postQuestions = $this->videoService->questionSet($videoId);

        if ($postQuestions->isEmpty()) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '该视频尚未配置题目');
        }

        $this->validateAnswers($postQuestions, $answers);

        $preQuestions = $this->videoService->questionSet($videoId);
        $preAnswers = collect($pretest->answers ?? []);

        $record = DB::transaction(function () use ($visit, $video, $drId, $pretest, $preQuestions, $preAnswers, $postQuestions, $answers) {
            $preGraded = $this->grade($preQuestions, $preAnswers);
            $postGraded = $this->grade($postQuestions, collect($answers));

            $preCorrect = $preGraded->where('is_correct', 1)->count();
            $preTotal = $preGraded->count();
            $postCorrect = $postGraded->where('is_correct', 1)->count();
            $postTotal = $postGraded->count();

            $accuracy = $postTotal > 0 ? (int) round($postCorrect / $postTotal * 100) : 0;
            $passAccuracy = $this->passAccuracy();

            $attemptNo = ExamRecord::query()
                ->where('user_id', $visit->id)
                ->where('video_id', $video->id)
                ->count() + 1;

            $record = ExamRecord::create([
                'user_id' => $visit->id,
                'dr_id' => $drId,
                'video_id' => $video->id,
                'attempt_no' => $attemptNo,
                'pretest_correct' => $preCorrect,
                'posttest_correct' => $postCorrect,
                'pretest_score' => $preTotal > 0 ? round($preCorrect / $preTotal * 100, 2) : 0,
                'posttest_score' => $postTotal > 0 ? round($postCorrect / $postTotal * 100, 2) : 0,
                'accuracy_rate' => $accuracy,
                'result_status' => $accuracy >= $passAccuracy ? 'pass' : 'fail',
                'total_duration' => (int) $preGraded->sum('duration') + (int) $postGraded->sum('duration'),
                'submit_at' => now(),
                'ip' => request()->ip(),
            ]);

            $this->createAnswers($record->id, 'pretest', $preGraded, $preTotal);
            $this->createAnswers($record->id, 'posttest', $postGraded, $postTotal);

            $pretest->delete();

            return $record;
        });

        Log::channel('business')->info('看完答题提交', [
            'exam_record_id' => $record->id,
            'dr_id' => $drId,
            'video_id' => $video->id,
            'attempt_no' => $record->attempt_no,
            'result_status' => $record->result_status,
        ]);

        return $this->buildResult($record);
    }

    /**
     * 再次答题（后测重答）：复用最近一次答题记录的前测结果，仅重评后测并生成新的一条答题记录
     *
     * @param  array<int, array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function retake(string $drId, int $videoId, array $answers): array
    {
        $visit = $this->videoService->resolveVisit($drId);
        $video = $this->videoService->onShelfVideo($videoId);
        $this->videoService->assertVisitVideo($visit, $video->id);

        $prior = ExamRecord::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $video->id)
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->first();

        if (! $prior) {
            throw new BusinessException(ResponseCode::STATUS_NOT_OPERABLE, '请先完成一遍答题后再重答');
        }

        $postQuestions = $this->videoService->questionSet($videoId);

        if ($postQuestions->isEmpty()) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '该视频尚未配置题目');
        }

        $this->validateAnswers($postQuestions, $answers);

        $record = DB::transaction(function () use ($visit, $video, $drId, $prior, $postQuestions, $answers) {
            $postGraded = $this->grade($postQuestions, collect($answers));

            $postCorrect = $postGraded->where('is_correct', 1)->count();
            $postTotal = $postGraded->count();

            $accuracy = $postTotal > 0 ? (int) round($postCorrect / $postTotal * 100) : 0;
            $passAccuracy = $this->passAccuracy();

            $attemptNo = ExamRecord::query()
                ->where('user_id', $visit->id)
                ->where('video_id', $video->id)
                ->count() + 1;

            $record = ExamRecord::create([
                'user_id' => $visit->id,
                'dr_id' => $drId,
                'video_id' => $video->id,
                'attempt_no' => $attemptNo,
                'pretest_correct' => $prior->pretest_correct,
                'posttest_correct' => $postCorrect,
                'pretest_score' => $prior->pretest_score,
                'posttest_score' => $postTotal > 0 ? round($postCorrect / $postTotal * 100, 2) : 0,
                'accuracy_rate' => $accuracy,
                'result_status' => $accuracy >= $passAccuracy ? 'pass' : 'fail',
                'total_duration' => (int) $postGraded->sum('duration'),
                'submit_at' => now(),
                'ip' => request()->ip(),
            ]);

            $this->copyPretestAnswers($record->id, $prior->id);
            $this->createAnswers($record->id, 'posttest', $postGraded, $postTotal);

            return $record;
        });

        Log::channel('business')->info('再次答题提交', [
            'exam_record_id' => $record->id,
            'dr_id' => $drId,
            'video_id' => $video->id,
            'attempt_no' => $record->attempt_no,
            'result_status' => $record->result_status,
        ]);

        return $this->buildResult($record);
    }

    /**
     * 复制前测作答明细：再次答题沿用首次答题的前测结果，仅重答后测
     */
    private function copyPretestAnswers(int $newRecordId, int $priorRecordId): void
    {
        ExamAnswer::query()
            ->where('exam_record_id', $priorRecordId)
            ->where('stage', 'pretest')
            ->get()
            ->each(fn (ExamAnswer $answer) => ExamAnswer::create([
                'exam_record_id' => $newRecordId,
                'question_id' => $answer->question_id,
                'stage' => 'pretest',
                'user_answer' => $answer->user_answer,
                'is_correct' => $answer->is_correct,
                'score' => $answer->score,
                'duration' => $answer->duration,
            ]));
    }

    /**
     * 获取答题结果（结果页首次进入/刷新/回看）
     *
     * @return array<string, mixed>
     */
    public function result(string $drId, ?int $videoId, ?int $examRecordId): array
    {
        if ($examRecordId) {
            $record = ExamRecord::query()->find($examRecordId);

            if (! $record) {
                throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '答题记录不存在');
            }

            return $this->buildResult($record);
        }

        $visit = $this->videoService->resolveVisit($drId);
        $this->videoService->assertVisitVideo($visit, (int) $videoId);

        $record = ExamRecord::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $videoId)
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '暂无答题记录');
        }

        return $this->buildResult($record);
    }

    /**
     * 观看进度门槛校验：达到配置百分比（默认 90%）才能提交后测。
     * 视频损坏（转码失败或无视频源）时跳过该校验，直接放行。
     */
    private function assertWatchProgressUnlocked(int $visitId, Video $video): void
    {
        if ($this->videoService->isBrokenVideo($video)) {
            return;
        }

        $required = $this->videoService->watchRequiredPercent();

        $percent = (int) (VideoProgress::query()
            ->where('user_id', $visitId)
            ->where('video_id', $video->id)
            ->value('progress_percent') ?? 0);

        if ($percent < $required) {
            throw new BusinessException(
                ResponseCode::RULE_LIMIT_EXCEEDED,
                sprintf('观看进度达到 %d%% 后才能作答看完答题（当前 %d%%）', $required, $percent)
            );
        }
    }

    /**
     * 校验答题内容：题目属于本视频、答案格式合法、题量完整
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @param  array<int, array<string, mixed>>  $answers
     */
    private function validateAnswers(Collection $questions, array $answers): void
    {
        $questionMap = $questions->keyBy('question_id');

        foreach ($answers as $answer) {
            $question = $questionMap->get($answer['question_id']);

            if (! $question) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '存在不属于本视频的题目');
            }

            $this->validateAnswerFormat($question, (string) $answer['user_answer']);
        }

        if (count($answers) < $questions->count()) {
            throw new BusinessException(ResponseCode::PARAM_ERROR, '请先完成所有题目再提交');
        }
    }

    /**
     * 按题型校验答案格式：判断题限「正确/错误」，选择题限选项内键值
     *
     * @param  array<string, mixed>  $question
     */
    private function validateAnswerFormat(array $question, string $userAnswer): void
    {
        if ($question['type'] === 'judge') {
            if (! in_array($userAnswer, ['正确', '错误'], true)) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '判断题答案格式不正确');
            }

            return;
        }

        $validKeys = collect($question['options'] ?? [])->pluck('key')->filter()->all();

        $keys = array_filter(array_map('trim', explode(',', $userAnswer)));

        if (empty($keys)) {
            throw new BusinessException(ResponseCode::PARAM_ERROR, '答案不能为空');
        }

        foreach ($keys as $key) {
            if (! in_array($key, $validKeys, true)) {
                throw new BusinessException(ResponseCode::PARAM_ERROR, '答案选项不存在');
            }
        }

        if ($question['type'] === 'single' && count($keys) !== 1) {
            throw new BusinessException(ResponseCode::PARAM_ERROR, '单选题只能选择一个答案');
        }
    }

    /**
     * 评分：以本视频题目配置为准，输出每个题目的作答明细（键为 question_id）
     *
     * @param  Collection<int, array<string, mixed>>  $questions
     * @param  Collection<int, array<string, mixed>>  $answers
     * @return Collection<int, array<string, mixed>>
     */
    private function grade(Collection $questions, Collection $answers): Collection
    {
        $answerMap = $answers->keyBy('question_id');

        return $questions->mapWithKeys(function (array $question) use ($answerMap) {
            $answer = $answerMap->get($question['question_id']);

            $isCorrect = 0;

            if ($answer !== null) {
                $isCorrect = $this->normalizeAnswer($question['type'], (string) $answer['user_answer'])
                    === $this->normalizeAnswer($question['type'], (string) $question['correct_answer']) ? 1 : 0;
            }

            return [
                $question['question_id'] => [
                    'question_id' => $question['question_id'],
                    'user_answer' => $answer['user_answer'] ?? '',
                    'is_correct' => $isCorrect,
                    'duration' => (int) ($answer['duration'] ?? 0),
                ],
            ];
        });
    }

    /**
     * 规范化答案：多选题按键值排序拼接，避免顺序差异导致误判
     */
    private function normalizeAnswer(string $type, string $answer): string
    {
        if ($type === 'multiple') {
            $parts = array_filter(array_map('trim', explode(',', $answer)));
            sort($parts);

            return implode(',', $parts);
        }

        return trim($answer);
    }

    /**
     * 批量写入答题明细
     *
     * @param  Collection<int, array<string, mixed>>  $graded
     */
    private function createAnswers(int $examRecordId, string $stage, Collection $graded, int $total): void
    {
        $perScore = $total > 0 ? round(100 / $total, 2) : 0;

        foreach ($graded as $item) {
            ExamAnswer::create([
                'exam_record_id' => $examRecordId,
                'question_id' => $item['question_id'],
                'stage' => $stage,
                'user_answer' => $item['user_answer'],
                'is_correct' => $item['is_correct'],
                'score' => $item['is_correct'] ? $perScore : 0,
                'duration' => $item['duration'],
            ]);
        }
    }

    /**
     * 组装答题结果（提交后测与获取结果页共用结构）
     *
     * @return array<string, mixed>
     */
    private function buildResult(ExamRecord $record): array
    {
        $record->loadMissing('video:id,title');

        $answers = ExamAnswer::query()
            ->with('question:id,type,stem,options,correct_answer,analysis')
            ->where('exam_record_id', $record->id)
            ->get();

        $questionIds = $answers->pluck('question_id')->unique()->values();

        // 题目顺序以本视频题目配置（sort_order）为准
        $orderedIds = VideoQuestion::query()
            ->where('video_id', $record->video_id)
            ->whereIn('question_id', $questionIds)
            ->orderBy('sort_order')
            ->orderBy('question_id')
            ->pluck('question_id')
            ->unique()
            ->values();

        $byQuestion = $answers->groupBy('question_id');

        $detail = $orderedIds->map(function (int $questionId) use ($byQuestion) {
            $items = $byQuestion->get($questionId, collect());
            $pre = $items->firstWhere('stage', 'pretest');
            $post = $items->firstWhere('stage', 'posttest');
            $question = ($post ?? $pre)?->question;

            $preCorrect = (int) ($pre->is_correct ?? 0);
            $postCorrect = (int) ($post->is_correct ?? 0);

            return [
                'question_id' => $questionId,
                'type' => $question->type ?? '',
                'stem' => $question->stem ?? '',
                'options' => $question->options ?? [],
                'correct_answer' => $question->correct_answer ?? '',
                'analysis' => $question->analysis ?? '',
                'pre_user_answer' => $pre->user_answer ?? '',
                'pre_is_correct' => $preCorrect,
                'post_user_answer' => $post->user_answer ?? '',
                'post_is_correct' => $postCorrect,
                'improved' => ($preCorrect === 0 && $postCorrect === 1) ? 1 : 0,
            ];
        })->values();

        $preTotal = $answers->where('stage', 'pretest')->count();
        $postTotal = $answers->where('stage', 'posttest')->count();
        $preCorrect = $answers->where('stage', 'pretest')->where('is_correct', 1)->count();
        $postCorrect = $answers->where('stage', 'posttest')->where('is_correct', 1)->count();

        $accuracy = $postTotal > 0 ? (int) round($postCorrect / $postTotal * 100) : 0;

        return [
            'exam_record_id' => $record->id,
            'video_id' => $record->video_id,
            'video_title' => $record->video->title ?? '',
            'result_status' => $record->result_status,
            'pass_accuracy' => $this->passAccuracy(),
            'accuracy_rate' => $accuracy,
            'improved_count' => $detail->where('improved', 1)->count(),
            'pre' => [
                'correct' => $preCorrect,
                'total' => $preTotal,
                'accuracy_rate' => $preTotal > 0 ? (int) round($preCorrect / $preTotal * 100) : 0,
            ],
            'post' => [
                'correct' => $postCorrect,
                'total' => $postTotal,
                'accuracy_rate' => $postTotal > 0 ? (int) round($postCorrect / $postTotal * 100) : 0,
            ],
            'question_detail' => $detail->all(),
        ];
    }

    /**
     * 通过线（默认 60，取自 system_config.pass_score）
     */
    private function passAccuracy(): int
    {
        $value = SystemConfig::query()
            ->where('config_key', 'pass_score')
            ->value('config_value');

        return $value !== null ? (int) $value : 60;
    }
}
