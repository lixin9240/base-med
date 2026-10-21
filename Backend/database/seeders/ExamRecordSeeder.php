<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\ExamAnswer;
use App\Models\ExamRecord;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Video;
use App\Models\VideoQuestion;
use App\Models\VisitRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 答题记录模块演示数据
 *
 * 由于答题记录依赖视频、题目、访问记录等，本 Seeder 自包含整条依赖链：
 * admin_user → category → topic → video → question → video_question
 * → visit_record → exam_record → exam_answer
 * 全部使用 firstOrCreate 幂等，可重复执行不产生重复数据。
 */
class ExamRecordSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminUser::firstOrCreate(
            ['username' => 'admin'],
            [
                'password_hash' => Hash::make('123456'),
                'name' => '系统管理员',
                'role' => 'admin',
                'status' => 1,
            ]
        );

        $category = Category::firstOrCreate(
            ['name' => '急救技能'],
            ['icon' => '', 'sort_order' => 1, 'status' => 1]
        );

        $topic = Topic::firstOrCreate(
            ['name' => '急诊急救基础'],
            ['cover_url' => '', 'intro' => '面向基层医生的急诊急救基础知识', 'sort_order' => 1, 'status' => 1]
        );

        $video = Video::firstOrCreate(
            ['title' => '心肺复苏基础操作'],
            [
                'category_id' => $category->id,
                'topic_id' => $topic->id,
                'speaker' => '郭军',
                'speaker_title' => '主任医师',
                'speaker_org' => '四川大学华西医院',
                'brief_intro' => '成人基础生命支持的规范动作：按压位置、深度、频率与通气比例。',
                'text_key_points' => '【按压位置】两乳头连线中点。【按压深度与频率】5~6厘米，100~120次/分。',
                'source_type' => 1,
                'video_url' => '/videos/cpr.mp4',
                'duration' => 120,
                'transcode_status' => 2,
                'sort_order' => 1,
                'status' => 1,
                'created_by' => $admin->id,
            ]
        );

        // 前端测试题 3 道 + 后测试题 3 道
        $questionDefs = [
            ['stage' => 'pretest', 'type' => 'single', 'stem' => '成人心肺复苏时，胸外按压的深度应为多少？', 'options' => [['key' => 'A', 'content' => '3~4厘米'], ['key' => 'B', 'content' => '4~5厘米'], ['key' => 'C', 'content' => '5~6厘米']], 'correct_answer' => 'C', 'analysis' => '成人胸外按压深度应为5~6厘米。', 'knowledge_point' => '心肺复苏'],
            ['stage' => 'pretest', 'type' => 'single', 'stem' => '成人胸外按压的频率应保持在每分钟多少次？', 'options' => [['key' => 'A', 'content' => '60~80次'], ['key' => 'B', 'content' => '80~100次'], ['key' => 'C', 'content' => '100~120次']], 'correct_answer' => 'C', 'analysis' => '成人胸外按压频率应为100~120次/分。', 'knowledge_point' => '心肺复苏'],
            ['stage' => 'pretest', 'type' => 'multiple', 'stem' => '判断心脏骤停的要点包括哪些？', 'options' => [['key' => 'A', 'content' => '意识丧失'], ['key' => 'B', 'content' => '无呼吸或濒死喘息'], ['key' => 'C', 'content' => '大动脉搏动消失']], 'correct_answer' => 'A,B,C', 'analysis' => '意识丧失、无正常呼吸、大动脉搏动消失是判断心脏骤停的关键。', 'knowledge_point' => '心肺复苏'],
            ['stage' => 'posttest', 'type' => 'multiple', 'stem' => '下列哪些情形可以停止心肺复苏？', 'options' => [['key' => 'A', 'content' => '患者恢复自主呼吸和心跳'], ['key' => 'B', 'content' => '专业急救人员到场接替'], ['key' => 'C', 'content' => '施救者精疲力尽无法继续']], 'correct_answer' => 'A,B,C', 'analysis' => '恢复自主循环、专业人员接替、施救者体力耗尽均可停止按压。', 'knowledge_point' => '心肺复苏'],
            ['stage' => 'posttest', 'type' => 'judge', 'stem' => '胸外按压中断时间不应超过10秒。', 'options' => [['key' => 'A', 'content' => '正确'], ['key' => 'B', 'content' => '错误']], 'correct_answer' => '正确', 'analysis' => '为维持有效灌注，胸外按压中断应控制在10秒以内。', 'knowledge_point' => '心肺复苏'],
            ['stage' => 'posttest', 'type' => 'single', 'stem' => '成人胸外按压与人工通气的比例应为？', 'options' => [['key' => 'A', 'content' => '15:2'], ['key' => 'B', 'content' => '30:2']], 'correct_answer' => 'B', 'analysis' => '成人基础生命支持推荐按压通气比为30:2。', 'knowledge_point' => '心肺复苏'],
        ];

        $questionModels = [];
        $stageOrder = ['pretest' => 0, 'posttest' => 0];
        foreach ($questionDefs as $index => $def) {
            $stageOrder[$def['stage']]++;

            $question = Question::firstOrCreate(
                ['stem' => $def['stem']],
                [
                    'type' => $def['type'],
                    'options' => $def['options'],
                    'option_count' => count($def['options']),
                    'correct_answer' => $def['correct_answer'],
                    'analysis' => $def['analysis'],
                    'difficulty' => 1,
                    'knowledge_point' => $def['knowledge_point'],
                    'created_by' => $admin->id,
                ]
            );

            VideoQuestion::firstOrCreate(
                ['video_id' => $video->id, 'stage' => $def['stage'], 'question_id' => $question->id],
                ['sort_order' => $stageOrder[$def['stage']]]
            );

            $questionModels[$index] = $question;
        }

        // 逐题作答计划（0~5 对应题目下标）
        $answerPlans = [
            'DR-2026-000003' => [
                'pretest' => [[0, 'C', 1], [1, 'C', 1], [2, 'A,B', 0]],
                'posttest' => [[3, 'A,B,C', 1], [4, '正确', 1], [5, 'B', 1]],
            ],
            'DR-2026-000001' => [
                'pretest' => [[0, 'C', 1], [1, 'B', 0], [2, 'A', 0]],
                'posttest' => [[3, 'A', 0], [4, '错误', 0], [5, 'A', 0]],
            ],
        ];

        $submitAtOffsets = ['DR-2026-000003' => 2, 'DR-2026-000001' => 4];
        $perQuestionScore = round(100 / 3, 2); // 每组 3 题，单题 33.33

        foreach ($answerPlans as $drId => $stages) {
            $visit = VisitRecord::firstOrCreate(
                ['dr_id' => $drId],
                [
                    'share_link_id' => null,
                    'video_id' => $video->id,
                    'entered_at' => now()->subDays($submitAtOffsets[$drId])->subHour(),
                    'created_at' => now()->subDays($submitAtOffsets[$drId])->subHour(),
                ]
            );

            $pretestCorrect = 0;
            $posttestCorrect = 0;
            foreach ($stages['pretest'] as $plan) {
                $pretestCorrect += $plan[2];
            }
            foreach ($stages['posttest'] as $plan) {
                $posttestCorrect += $plan[2];
            }

            $accuracyRate = $posttestCorrect > 0
                ? (int) round($posttestCorrect / count($stages['posttest']) * 100)
                : 0;

            $record = ExamRecord::firstOrCreate(
                ['user_id' => $visit->id, 'video_id' => $video->id, 'attempt_no' => 1],
                [
                    'dr_id' => $drId,
                    'pretest_correct' => $pretestCorrect,
                    'posttest_correct' => $posttestCorrect,
                    'pretest_score' => $pretestCorrect * $perQuestionScore,
                    'posttest_score' => $posttestCorrect * $perQuestionScore,
                    'accuracy_rate' => $accuracyRate,
                    'result_status' => $accuracyRate >= 60 ? 'pass' : 'fail',
                    'total_duration' => 300 + ($submitAtOffsets[$drId] * 10),
                    'submit_at' => now()->subDays($submitAtOffsets[$drId]),
                    'ip' => '127.0.0.1',
                    'created_at' => now()->subDays($submitAtOffsets[$drId]),
                ]
            );

            foreach ($stages as $stage => $plans) {
                foreach ($plans as $plan) {
                    [$questionIndex, $userAnswer, $isCorrect] = $plan;

                    ExamAnswer::firstOrCreate(
                        ['exam_record_id' => $record->id, 'question_id' => $questionModels[$questionIndex]->id],
                        [
                            'stage' => $stage,
                            'user_answer' => $userAnswer,
                            'is_correct' => $isCorrect,
                            'score' => $isCorrect ? $perQuestionScore : 0,
                            'duration' => 15 + $questionIndex * 3,
                            'created_at' => $record->submit_at,
                        ]
                    );
                }
            }
        }
    }
}