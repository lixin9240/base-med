<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 靶场测试数据（本地隔离环境 base_med_lab 专用）
 *
 * 只允许在本地隔离库上执行，禁止对共享库使用：
 *   DB_HOST=127.0.0.1 DB_DATABASE=base_med_lab DB_USERNAME=root DB_PASSWORD=*** \
 *     php artisan db:seed --class=LabSeeder --force
 */
class LabSeeder extends Seeder
{
    public function run(): void
    {
        // 管理员：1 个超管 + 2 个普通（密码均为 Lab#Admin2026 / Lab#User2026）
        AdminUser::create([
            'username' => 'labadmin',
            'password_hash' => Hash::make('Lab#Admin2026'),
            'name' => '靶场超管',
            'email' => 'labadmin@example.com',
            'role' => 'super',
            'status' => 1,
        ]);
        AdminUser::create([
            'username' => 'labzhang',
            'password_hash' => Hash::make('Lab#User2026'),
            'name' => '张医生',
            'email' => 'labzhang@example.com',
            'role' => 'admin',
            'status' => 1,
        ]);
        AdminUser::create([
            'username' => 'labli',
            'password_hash' => Hash::make('Lab#User2026'),
            'name' => '李医生',
            'email' => 'labli@example.com',
            'role' => 'admin',
            'status' => 0, // 预置一个禁用号，验证禁用逻辑
        ]);

        // 分类
        foreach ([['高血压防治', 1], ['糖尿病防治', 2], ['呼吸道疾病防治', 3]] as [$name, $sort]) {
            DB::table('category')->insert([
                'name' => $name, 'icon' => '', 'sort_order' => $sort, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 课程
        foreach ([['基层高血压规范化管理', 1], ['2型糖尿病基层用药', 2]] as [$name, $sort]) {
            DB::table('topic')->insert([
                'name' => $name, 'cover_url' => '', 'intro' => "《{$name}》系列课程",
                'sort_order' => $sort, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 题库（单选）——options 必须为 [{key,content}] 对象数组，type 为字符串
        $questions = [
            ['高血压诊断界值是多少？', ['收缩压≥140 和/或舒张压≥90', '收缩压≥130', '舒张压≥100', '血压≥120/80'], 'A', '高血压', '高血压诊断标准'],
            ['糖尿病患者空腹血糖控制目标一般为', ['4.4-7.0 mmol/L', '<11.1 mmol/L', '>7.8 mmol/L', '无需控制'], 'A', '糖尿病', '血糖控制目标'],
            ['一级预防的核心是', ['生活方式干预', '长期服药', '定期输液', '手术干预'], 'A', '高血压', '一级预防'],
            ['测量血压前应安静休息至少', ['5 分钟', '1 分钟', '30 秒', '无需休息'], 'A', '高血压', '血压测量规范'],
        ];
        foreach ($questions as $i => [$stem, $opts, $answer, $kp, $analysis]) {
            DB::table('question')->insert([
                'type' => 'single', 'stem' => $stem, 'image_url' => '',
                'options' => json_encode(
                    array_map(fn ($i2, $c) => ['key' => ['A', 'B', 'C', 'D', 'E', 'F'][$i2], 'content' => $c], array_keys($opts), array_values($opts)),
                    JSON_UNESCAPED_UNICODE
                ),
                'option_count' => 4,
                'correct_answer' => $answer, 'analysis' => $analysis, 'difficulty' => 1,
                'knowledge_point' => $kp, 'created_by' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 视频
        $videos = [
            ['高血压的五大危险因素', 1, 1, 'https://example.com/lab/v1.mp4', 360, 1],
            ['糖尿病足的日常护理', 2, 1, 'https://example.com/lab/v2.mp4', 480, 1],
            ['流感与普通感冒的区别', 3, 2, 'https://example.com/lab/v3.mp4', 300, 1],
            ['高血压用药常见误区（图文）', 1, 1, '', 0, 1],
        ];
        foreach ($videos as $i => [$title, $cat, $topic, $url, $duration, $status]) {
            DB::table('video')->insert([
                'title' => $title, 'category_id' => $cat, 'topic_id' => $topic,
                'speaker' => '王医生', 'speaker_title' => '主任医师', 'speaker_org' => '县人民医院',
                'brief_intro' => "{$title}——面向基层医生的 DR 医学科普。",
                'text_key_points' => "<p>要点一：早筛查早干预</p><p>要点二：规范用药</p>",
                'source_type' => 2, 'video_url' => $url, 'oss_path' => '', 'oss_upload_id' => '',
                'cover_url' => '', 'duration' => $duration, 'file_size' => 0,
                'transcode_status' => 2, 'sort_order' => $i + 1, 'status' => $status,
                'created_by' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 视频配题：前测(1题)+后测(2题) / 其余仅后测（stage 必须为 pretest/posttest 字符串）
        $bindings = [
            1 => [[1, 'pretest', 1], [2, 'posttest', 1], [4, 'posttest', 2]],   // video_id => [question_id, stage, sort]
            2 => [[3, 'pretest', 1], [1, 'posttest', 1], [2, 'posttest', 2]],
            3 => [[4, 'posttest', 1]],
            4 => [[2, 'posttest', 1]],
        ];
        foreach ($bindings as $videoId => $rows) {
            foreach ($rows as [$qid, $stage, $sort]) {
                DB::table('video_question')->insert([
                    'video_id' => $videoId, 'stage' => $stage, 'question_id' => $qid,
                    'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // 机构（video_share_link.org_id 外键依赖）
        DB::table('organization')->insert([
            'name' => '靶场社区卫生服务中心', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 视频分享链接（visit_record.share_link_id 外键依赖）
        $links = [[1, 'labtoken000000000000000000000001', 1], [2, 'labtoken000000000000000000000002', 1], [3, 'labtoken000000000000000000000003', 0]];
        foreach ($links as $i => [$videoId, $token, $status]) {
            DB::table('video_share_link')->insert([
                'video_id' => $videoId, 'org_id' => 1, 'token' => $token,
                'expire_type' => 'never', 'expire_at' => null, 'visit_count' => 0,
                'status' => $status, 'created_by' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 访问记录（匿名 DR 编号，库内 dr_id 唯一）
        $visits = [
            ['DR-LAB-0001', 1, 1], ['DR-LAB-0002', 2, 2], ['DR-LAB-0003', 1, 3], ['DR-LAB-0004', 3, 1],
        ];
        foreach ($visits as $i => [$drId, $videoId, $shareLinkId]) {
            DB::table('visit_record')->insert([
                'dr_id' => $drId, 'share_link_id' => $shareLinkId, 'video_id' => $videoId,
                'entered_at' => now()->subHours($i + 1), 'created_at' => now()->subHours($i + 1),
            ]);
        }

        // 观看进度
        $progress = [
            ['DR-LAB-0001', 1, 1, 320, 360, 88.9, 1],  // 差一点解锁
            ['DR-LAB-0002', 2, 1, 360, 360, 100.0, 1], // 已解锁
            ['DR-LAB-0003', 1, 2, 100, 480, 20.8, 0],
        ];
        foreach ($progress as [$drId, $userId, $videoId, $watched, $duration, $percent, $unlocked]) {
            DB::table('video_progress')->insert([
                'user_id' => $userId, 'dr_id' => $drId, 'video_id' => $videoId,
                'watched_seconds' => $watched, 'duration' => $duration,
                'progress_percent' => $percent, 'is_unlocked' => $unlocked,
                'last_watched_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 答题记录 + 答案
        DB::table('exam_record')->insert([
            'user_id' => 1, 'dr_id' => 'DR-LAB-0002', 'video_id' => 1, 'attempt_no' => 1,
            'pretest_correct' => 0, 'posttest_correct' => 2, 'pretest_score' => 0,
            'posttest_score' => 100, 'accuracy_rate' => 100.0, 'result_status' => 2,
            'total_duration' => 120, 'submit_at' => now()->subHour(), 'ip' => '127.0.0.1',
            'created_at' => now()->subHour(),
        ]);
        $recordId = (int) DB::getPdo()->lastInsertId();
        foreach ([[4, 2, 'A', 1, 100], [2, 2, 'A', 1, 0]] as [$qid, $stage, $ans, $correct, $score]) {
            DB::table('exam_answer')->insert([
                'exam_record_id' => $recordId, 'question_id' => $qid, 'stage' => $stage,
                'user_answer' => $ans, 'is_correct' => $correct, 'score' => $score,
                'duration' => 30, 'created_at' => now()->subHour(),
            ]);
        }

        // 操作日志
        foreach ([['登录', 'login', 1], ['新增视频', 'create', 1]] as $i => [$action, $act, $target]) {
            DB::table('operation_log')->insert([
                'admin_id' => 1, 'module' => 'video', 'action' => $act,
                'target_id' => $target, 'detail' => '{"seeder":true}',
                'ip' => '127.0.0.1', 'created_at' => now()->subMinutes(30 - $i),
            ]);
        }
    }
}
