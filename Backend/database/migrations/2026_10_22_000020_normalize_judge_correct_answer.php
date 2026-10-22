<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 判断题正确答案编码统一为「正确/错误」，与用户端 user_answer 保持同一套编码。
 *
 * 同时重判判断题作答明细并重算受影响答题记录的汇总字段，
 * 修复此前 correct_answer 存 A/B 导致中文作答被判错的问题。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('question')
            ->where('type', 'judge')
            ->whereRaw('UPPER(TRIM(correct_answer)) = ?', ['A'])
            ->update(['correct_answer' => '正确']);

        DB::table('question')
            ->where('type', 'judge')
            ->whereRaw('UPPER(TRIM(correct_answer)) = ?', ['B'])
            ->update(['correct_answer' => '错误']);

        $this->regradeJudgeAnswers();
    }

    /**
     * Reverse the migrations.
     *
     * 回滚会把全部判断题答案改回字母编码（恢复迁移前的判定口径），并同步重算评分。
     */
    public function down(): void
    {
        DB::table('question')
            ->where('type', 'judge')
            ->where('correct_answer', '正确')
            ->update(['correct_answer' => 'A']);

        DB::table('question')
            ->where('type', 'judge')
            ->where('correct_answer', '错误')
            ->update(['correct_answer' => 'B']);

        $this->regradeJudgeAnswers();
    }

    /**
     * 按 question.correct_answer 重新判定判断题答案，并重算受影响答题记录的汇总字段
     */
    private function regradeJudgeAnswers(): void
    {
        $rows = DB::table('exam_answer')
            ->join('question', 'question.id', '=', 'exam_answer.question_id')
            ->where('question.type', 'judge')
            ->select([
                'exam_answer.id',
                'exam_answer.exam_record_id',
                'exam_answer.stage',
                'exam_answer.user_answer',
                'exam_answer.is_correct',
                'exam_answer.score',
                'question.correct_answer',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $recordIds = $rows->pluck('exam_record_id')->unique()->values()->all();

        // 各记录、各环节的题量，用于还原单题分值
        $totals = DB::table('exam_answer')
            ->whereIn('exam_record_id', $recordIds)
            ->groupBy('exam_record_id', 'stage')
            ->selectRaw('exam_record_id, stage, COUNT(*) AS total')
            ->get()
            ->keyBy(fn ($row) => $row->exam_record_id . ':' . $row->stage);

        $touchedRecordIds = [];

        foreach ($rows as $row) {
            $isCorrect = trim((string) $row->user_answer) === trim((string) $row->correct_answer) ? 1 : 0;

            $total = (int) ($totals[$row->exam_record_id . ':' . $row->stage]->total ?? 0);
            $score = $isCorrect === 1 && $total > 0 ? round(100 / $total, 2) : 0;

            if ((int) $row->is_correct === $isCorrect && (float) $row->score === (float) $score) {
                continue;
            }

            DB::table('exam_answer')->where('id', $row->id)->update([
                'is_correct' => $isCorrect,
                'score' => $score,
            ]);

            $touchedRecordIds[$row->exam_record_id] = true;
        }

        foreach (array_keys($touchedRecordIds) as $examRecordId) {
            $this->refreshExamRecord((int) $examRecordId);
        }
    }

    /**
     * 由答题明细重算答题记录的得分、正确率与通过状态
     */
    private function refreshExamRecord(int $examRecordId): void
    {
        $answers = DB::table('exam_answer')->where('exam_record_id', $examRecordId)->get();

        if ($answers->isEmpty()) {
            return;
        }

        $pre = $answers->where('stage', 'pretest');
        $post = $answers->where('stage', 'posttest');

        $preTotal = $pre->count();
        $postTotal = $post->count();
        $preCorrect = $pre->where('is_correct', 1)->count();
        $postCorrect = $post->where('is_correct', 1)->count();

        $accuracy = $postTotal > 0 ? (int) round($postCorrect / $postTotal * 100) : 0;

        $passScore = DB::table('system_config')->where('config_key', 'pass_score')->value('config_value');
        $passAccuracy = $passScore !== null ? (int) $passScore : 60;

        DB::table('exam_record')->where('id', $examRecordId)->update([
            'pretest_correct' => $preCorrect,
            'posttest_correct' => $postCorrect,
            'pretest_score' => $preTotal > 0 ? round($preCorrect / $preTotal * 100, 2) : 0,
            'posttest_score' => $postTotal > 0 ? round($postCorrect / $postTotal * 100, 2) : 0,
            'accuracy_rate' => $accuracy,
            'result_status' => $accuracy >= $passAccuracy ? 'pass' : 'fail',
            'total_duration' => (int) $answers->sum('duration'),
        ]);
    }
};
