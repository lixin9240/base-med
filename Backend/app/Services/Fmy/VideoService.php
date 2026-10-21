<?php

namespace App\Services\Fmy;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\ExamPretest;
use App\Models\ExamRecord;
use App\Models\SystemConfig;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Models\VideoQuestion;
use App\Models\VisitRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VideoService
{
    /**
     * 用户端首页视频列表（免登录展示全部上架视频，点击视频时才签发 dr_id）
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return Video::query()
            ->with('category:id,name')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Video $video) => [
                'id' => $video->id,
                'title' => $video->title,
                'category_name' => $video->category->name ?? '',
                'speaker' => $video->speaker,
                'brief_intro' => $video->brief_intro,
                'cover_url' => $video->cover_url ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * 视频详情（观看视频页）
     *
     * @return array<string, mixed>
     */
    public function detail(string $drId, int $videoId): array
    {
        $visit = $this->resolveVisit($drId);
        $video = $this->onShelfVideo($videoId);
        $this->assertVisitVideo($visit, $video->id);

        $pretestSubmitted = ExamPretest::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $video->id)
            ->exists();

        $examRecord = ExamRecord::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $video->id)
            ->orderByDesc('attempt_no')
            ->orderByDesc('id')
            ->first();

        $progress = VideoProgress::query()
            ->where('user_id', $visit->id)
            ->where('video_id', $video->id)
            ->first();

        $required = $this->watchRequiredPercent();

        return [
            'id' => $video->id,
            'title' => $video->title,
            'category_name' => $video->category->name ?? '',
            'speaker' => $video->speaker,
            'speaker_title' => $video->speaker_title,
            'speaker_org' => $video->speaker_org,
            'brief_intro' => $video->brief_intro,
            'text_key_points' => $video->text_key_points,
            'video_url' => $video->video_url ?? '',
            'cover_url' => $video->cover_url ?? '',
            'duration' => (int) $video->duration,
            'exam_state' => [
                'pre_submitted' => $pretestSubmitted ? 1 : 0,
                'post_submitted' => $examRecord ? 1 : 0,
                'exam_record_id' => $examRecord?->id,
            ],
            'watch_progress' => [
                'watched_seconds' => (int) ($progress->watched_seconds ?? 0),
                'progress_percent' => (int) ($progress->progress_percent ?? 0),
                'watch_required_percent' => $required,
                'is_unlocked' => ($this->isBrokenVideo($video) || ($progress && (int) $progress->progress_percent >= $required)) ? 1 : 0,
            ],
        ];
    }

    /**
     * 获取视频题目列表（学前/看完答题页共用，不返回正确答案与解析）
     *
     * @return array<int, array<string, mixed>>
     */
    public function questionList(int $videoId): array
    {
        $this->onShelfVideo($videoId);

        return $this->questionSet($videoId)
            ->map(fn (array $question) => [
                'question_id' => $question['question_id'],
                'type' => $question['type'],
                'stem' => $question['stem'],
                'image_url' => $question['image_url'] ?? '',
                'options' => $question['options'],
            ])
            ->values()
            ->all();
    }

    /**
     * 上报视频观看进度（取已看时长最大值，计算进度百分比并解锁后测门槛）
     *
     * @return array<string, mixed>
     */
    public function progress(string $drId, int $videoId, int $watchedSeconds, ?int $clientDuration): array
    {
        $visit = $this->resolveVisit($drId);
        $video = $this->onShelfVideo($videoId);
        $this->assertVisitVideo($visit, $video->id);

        $required = $this->watchRequiredPercent();

        $progress = DB::transaction(function () use ($visit, $video, $drId, $watchedSeconds, $clientDuration, $required) {
            $row = VideoProgress::query()
                ->where('user_id', $visit->id)
                ->where('video_id', $video->id)
                ->lockForUpdate()
                ->first();

            $duration = (int) $video->duration;

            if ($duration <= 0 && $clientDuration !== null && $clientDuration > 0) {
                // 服务端无视频时长时（如填写链接未回填），暂采用前端上报的时长计算
                $duration = $clientDuration;
            }

            $watched = max((int) ($row->watched_seconds ?? 0), $watchedSeconds);
            $percent = $this->calcPercent($watched, $duration);

            $attributes = [
                'dr_id' => $drId,
                'watched_seconds' => $watched,
                'duration' => $duration,
                'progress_percent' => $percent,
                'is_unlocked' => ($this->isBrokenVideo($video) || $percent >= $required) ? 1 : 0,
                'last_watched_at' => now(),
            ];

            if ($row) {
                $row->update($attributes);
            } else {
                $row = VideoProgress::create(array_merge([
                    'user_id' => $visit->id,
                    'video_id' => $video->id,
                ], $attributes));
            }

            return $row;
        });

        return [
            'watched_seconds' => (int) $progress->watched_seconds,
            'duration' => (int) $progress->duration,
            'progress_percent' => (int) $progress->progress_percent,
            'watch_required_percent' => $required,
            'is_unlocked' => (int) $progress->is_unlocked,
        ];
    }

    /**
     * 按 dr_id 解析匿名访问记录（用户端接口共用入口）
     */
    public function resolveVisit(string $drId): VisitRecord
    {
        $visit = VisitRecord::query()->where('dr_id', $drId)->first();

        if (! $visit) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '匿名编号不存在');
        }

        return $visit;
    }

    /**
     * 校验匿名编号与视频匹配（dr_id 为点击某条视频时签发，仅对该视频有效）
     */
    public function assertVisitVideo(VisitRecord $visit, int $videoId): void
    {
        if ($visit->video_id === null || (int) $visit->video_id !== (int) $videoId) {
            throw new BusinessException(ResponseCode::PARAM_ERROR, '匿名编号与视频不匹配');
        }
    }

    /**
     * 获取已上架视频，不存在或已下架时抛业务异常
     */
    public function onShelfVideo(int $videoId): Video
    {
        $video = Video::query()
            ->with('category:id,name')
            ->where('status', 1)
            ->find($videoId);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在或已下架');
        }

        return $video;
    }

    /**
     * 判断视频是否已损坏：转码失败（transcode_status=3）或 无可用视频源（video_url/oss_path 均空）。
     * 损坏视频无法正常观看，传入的视频不再进行观看进度门槛校验。
     */
    public function isBrokenVideo(Video $video): bool
    {
        if ((int) $video->transcode_status === 3) {
            return true;
        }

        return blank($video->video_url) && blank($video->oss_path);
    }

    /**
     * 视频题目集合（前后测同一套题；未指定环节时优先取前测配置，为空则取后测配置）
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function questionSet(int $videoId, ?string $stage = null): Collection
    {
        $stages = $stage ? [$stage] : ['pretest', 'posttest'];

        foreach ($stages as $item) {
            $questions = VideoQuestion::query()
                ->with('question:id,type,stem,image_url,options,correct_answer,analysis')
                ->where('video_id', $videoId)
                ->where('stage', $item)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->filter(fn (VideoQuestion $row) => $row->question !== null)
                ->map(fn (VideoQuestion $row) => [
                    'question_id' => $row->question_id,
                    'type' => $row->question->type,
                    'stem' => $row->question->stem,
                    'image_url' => $row->question->image_url,
                    'options' => $row->question->options,
                    'correct_answer' => $row->question->correct_answer,
                    'analysis' => $row->question->analysis,
                ]);

            if ($questions->isNotEmpty()) {
                return $questions->values();
            }
        }

        return collect();
    }

    /**
     * 后测解锁所需观看进度阈值（默认 90，取自 system_config.watch_required_percent）
     */
    public function watchRequiredPercent(): int
    {
        $value = SystemConfig::query()
            ->where('config_key', 'watch_required_percent')
            ->value('config_value');

        return $value !== null ? (int) $value : 90;
    }

    /**
     * 计算观看进度百分比（观看时长超过总时长时按 100% 计）
     */
    private function calcPercent(int $watchedSeconds, int $duration): int
    {
        if ($duration <= 0) {
            return 0;
        }

        return (int) round(min($watchedSeconds, $duration) / $duration * 100);
    }
}
