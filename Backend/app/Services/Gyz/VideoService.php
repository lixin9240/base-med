<?php

namespace App\Services\Gyz;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Category;
use App\Models\ExamRecord;
use App\Models\SystemConfig;
use App\Models\Topic;
use App\Models\Video;
use App\Models\VideoShareLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 视频管理服务
 *
 * 只做业务逻辑：视频列表、详情、新增、编辑、上下架、删除、本地上传。
 */
class VideoService
{
    /**
     * 分片大小（字节）：1MB
     */
    private const PART_SIZE = 1048576;

    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 2.2.1 视频列表
     *
     * @param  array<string, mixed>  $params  已校验参数：page、page_size
     * @return array{list: array<int, array<string, mixed>>, total: int, page: int, page_size: int}
     */
    public function list(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $paginator = Video::query()
            ->with('category:id,name')
            ->withCount([
                'videoQuestions as question_count',
                'examRecords as answer_times',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        // 各视频平均正确率（无人答题时无记录）
        $videoIds = $paginator->getCollection()->pluck('id')->all();
        $avgRates = empty($videoIds)
            ? collect()
            : ExamRecord::query()
                ->whereIn('video_id', $videoIds)
                ->selectRaw('video_id, ROUND(AVG(accuracy_rate)) AS avg_rate')
                ->groupBy('video_id')
                ->pluck('avg_rate', 'video_id');

        // 建议题数取自 system_config（默认 3），前后测共用一套
        $questionTotal = $this->configValue('pretest_count', 3);

        $list = $paginator->getCollection()->map(function (Video $video) use ($avgRates, $questionTotal) {
            $questionCount = (int) $video->question_count;

            return [
                'id' => (int) $video->id,
                'title' => $video->title,
                'category_name' => $video->category->name ?? null,
                'cover_url' => $video->cover_url ?? '',
                'speaker' => $video->speaker,
                'speaker_title' => $video->speaker_title,
                'question_count' => $questionCount,
                'question_total' => $questionTotal,
                // 兼容前端旧字段：前后测共用一套，pre/post 均取整套题数
                'pre_count' => $questionCount,
                'pre_total' => $questionTotal,
                'post_count' => $questionCount,
                'post_total' => $questionTotal,
                'status' => (int) $video->status,
                'transcode_status' => (int) $video->transcode_status,
                'answer_times' => (int) $video->answer_times,
                'accuracy_rate' => $avgRates->has($video->id) ? (int) $avgRates[$video->id] : null,
            ];
        })->values()->all();

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 2.2.2 视频详情（编辑弹窗回填）
     *
     * @param  int  $id  视频 ID
     * @return array<string, mixed>
     */
    public function detail(int $id): array
    {
        $video = Video::query()->with('category:id,name')->find($id);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        return [
            'id' => (int) $video->id,
            'title' => $video->title,
            'category_id' => (int) $video->category_id,
            'category_name' => $video->category->name ?? null,
            'speaker' => $video->speaker,
            'speaker_title' => $video->speaker_title,
            'speaker_org' => $video->speaker_org,
            'brief_intro' => $video->brief_intro,
            'source_type' => (int) $video->source_type,
            'video_url' => $video->video_url ?? '',
            'cover_url' => $video->cover_url ?? '',
            'text_key_points' => $video->text_key_points,
            'status' => (int) $video->status,
            'sort_order' => (int) $video->sort_order,
            'duration' => (int) $video->duration,
            'transcode_status' => (int) $video->transcode_status,
        ];
    }

    /**
     * 2.2.3 新增视频（弹窗保存）
     *
     * @param  array<string, mixed>  $data  已校验参数
     * @return int 视频 ID
     */
    public function create(array $data): int
    {
        $adminId = (int) Auth::guard('admin')->id();

        $this->ensureCategoryExists((int) $data['category_id']);

        $video = Video::create([
            'title' => $data['title'],
            'category_id' => $data['category_id'],
            // 文档 2.2.3 未包含课程参数，但 video.topic_id 为非空外键，服务端兜底解析
            'topic_id' => $this->resolveTopicId($data['topic_id'] ?? null),
            'speaker' => $data['speaker'],
            // speaker_title/speaker_org/text_key_points 库里 NOT NULL 无默认值，未传时落空串
            'speaker_title' => $data['speaker_title'] ?? '',
            'speaker_org' => $data['speaker_org'] ?? '',
            'brief_intro' => $data['brief_intro'],
            'text_key_points' => $data['text_key_points'] ?? '',
            'source_type' => $data['source_type'],
            // source_type=2（本地上传）时可先不传 video_url，由 2.2.7/2.2.8 完成上传后回填
            'video_url' => $data['video_url'] ?? '',
            'cover_url' => $data['cover_url'] ?? '',
            // 填写链接的视频无转码流程恒为已完成（2）；本地上传完成后由 2.2.8 回填
            'transcode_status' => (int) $data['source_type'] === 1 ? 2 : 0,
            'sort_order' => $data['sort_order'] ?? 5,
            'status' => $data['status'],
            'created_by' => $adminId,
        ]);

        $this->operationLogService->record('video', 'create', (int) $video->id, [
            'title' => $video->title,
        ]);

        Log::channel('business')->info('新增视频', [
            'admin_id' => $adminId,
            'video_id' => $video->id,
            'title' => $video->title,
            'source_type' => $data['source_type'],
        ]);

        return (int) $video->id;
    }

    /**
     * 2.2.4 编辑视频（弹窗保存）
     *
     * @param  array<string, mixed>  $data  已校验参数
     */
    public function update(array $data): void
    {
        $video = Video::find($data['id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $this->ensureCategoryExists((int) $data['category_id']);

        $video->update([
            'title' => $data['title'],
            'category_id' => $data['category_id'],
            // 未传课程参数时保持原归属
            'topic_id' => $this->resolveTopicId($data['topic_id'] ?? null, (int) $video->topic_id),
            'speaker' => $data['speaker'],
            'speaker_title' => $data['speaker_title'] ?? '',
            'speaker_org' => $data['speaker_org'] ?? '',
            'brief_intro' => $data['brief_intro'],
            'text_key_points' => $data['text_key_points'] ?? '',
            'source_type' => $data['source_type'],
            'video_url' => $data['video_url'] ?? $video->video_url,
            'cover_url' => $data['cover_url'] ?? $video->cover_url,
            // 改为填写链接时无转码流程，恒为已完成（2）
            'transcode_status' => (int) $data['source_type'] === 1 ? 2 : (int) $video->transcode_status,
            'sort_order' => $data['sort_order'] ?? $video->sort_order,
            'status' => $data['status'],
        ]);

        $this->operationLogService->record('video', 'update', (int) $video->id, [
            'title' => $video->title,
        ]);

        Log::channel('business')->info('编辑视频', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'video_id' => $video->id,
            'title' => $video->title,
        ]);
    }

    /**
     * 2.2.5 上架/下架切换（列表页开关）
     *
     * @param  int  $id  视频 ID
     * @param  int  $status  0 已下架 1 已上架
     */
    public function updateStatus(int $id, int $status): void
    {
        $video = Video::find($id);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $video->update(['status' => $status]);

        $this->operationLogService->record('video', 'update', $id, [
            'title' => $video->title,
            'status' => $status,
        ]);

        Log::channel('business')->info('切换视频上下架', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'video_id' => $id,
            'status' => $status,
        ]);
    }

    /**
     * 2.2.6 删除视频（软删除；存在答题记录/访问链接等引用时不允许删除）
     *
     * @param  int  $id  视频 ID
     */
    public function delete(int $id): void
    {
        $video = Video::find($id);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        // 前置校验：已存在答题记录时不允许删除
        $recordCount = ExamRecord::query()->where('video_id', $id)->count();

        if ($recordCount > 0) {
            throw new BusinessException(
                ResponseCode::DATA_RELATION_EXISTS,
                '该视频已存在答题记录，无法删除'
            );
        }

        // 前置校验：已存在访问链接时不允许删除
        $linkCount = VideoShareLink::query()->where('video_id', $id)->count();

        if ($linkCount > 0) {
            throw new BusinessException(
                ResponseCode::DATA_RELATION_EXISTS,
                '该视频已存在访问链接，无法删除'
            );
        }

        $video->delete();

        $this->operationLogService->record('video', 'delete', $id, [
            'title' => $video->title,
        ]);

        Log::channel('business')->info('删除视频', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'video_id' => $id,
            'title' => $video->title,
        ]);
    }

    /**
     * 2.2.7 初始化视频上传（本地上传，source_type=2）
     *
     * 若该视频存在未完成的上传任务（oss_upload_id 已存在且未完成），直接返回原
     * oss_upload_id，实现断点续传（R6）。
     *
     * @param  array<string, mixed>  $data  已校验参数：file_name、file_size、video_id
     * @return array<string, mixed>
     */
    public function uploadInit(array $data): array
    {
        $partSize = self::PART_SIZE;
        $parts = (int) max(1, ceil($data['file_size'] / $partSize));
        $videoId = isset($data['video_id']) ? (int) $data['video_id'] : null;

        if ($videoId !== null) {
            $video = Video::find($videoId);

            if (! $video) {
                throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
            }

            // 断点续传：返回原上传任务
            if ($video->oss_upload_id && (int) $video->transcode_status !== 2) {
                Log::channel('business')->info('视频上传断点续传', [
                    'admin_id' => (int) Auth::guard('admin')->id(),
                    'video_id' => $videoId,
                    'oss_upload_id' => $video->oss_upload_id,
                ]);

                return [
                    'oss_upload_id' => $video->oss_upload_id,
                    'oss_path' => $video->oss_path,
                    'part_size' => $partSize,
                    'parts' => $parts,
                ];
            }
        }

        $uploadId = 'upload_' . bin2hex(random_bytes(8));
        $ossPath = 'video/' . date('Y/m') . '/' . $uploadId . '_' . $this->sanitizeFileName($data['file_name']);

        if ($videoId !== null) {
            $video->update([
                'oss_upload_id' => $uploadId,
                'oss_path' => $ossPath,
                'transcode_status' => 0,
            ]);
        }

        Log::channel('business')->info('初始化视频分片上传', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'video_id' => $videoId,
            'oss_upload_id' => $uploadId,
            'file_size' => $data['file_size'],
            'parts' => $parts,
        ]);

        return [
            'oss_upload_id' => $uploadId,
            'oss_path' => $ossPath,
            'part_size' => $partSize,
            'parts' => $parts,
        ];
    }

    /**
     * 本地模拟辅助：上传单个分片
     *
     * 真实环境使用 OSS 直传时，分片由前端 SDK 直传 OSS，可跳过本接口。
     *
     * @param  array<string, mixed>  $data  已校验参数：oss_upload_id、part_number、file
     */
    public function uploadChunk(array $data): void
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        $dir = 'video/parts/' . $data['oss_upload_id'];
        $disk = Storage::disk('oss');

        $saved = $disk->put(
            $dir . '/part_' . $data['part_number'],
            fopen($file->getRealPath(), 'rb')
        );

        if (! $saved) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED);
        }
    }

    /**
     * 上传封面图片（存 OSS）
     *
     * @param  array<string, mixed>  $data  已校验参数：file（图片）
     * @return array<string, mixed>  上传成功后的访问地址
     */
    public function uploadCover(array $data): array
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $ossPath = 'cover/' . date('Y/m') . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($extension);

        $disk = Storage::disk('oss');

        $saved = $disk->put($ossPath, fopen($file->getRealPath(), 'rb'));

        if (! $saved) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED);
        }

        Log::channel('business')->info('上传封面图片', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'oss_path' => $ossPath,
            'size' => $file->getSize(),
        ]);

        return [
            'url' => $disk->url($ossPath),
        ];
    }

    /**
     * 2.2.8 完成视频上传
     *
     * 合并分片后自动触发转码；转码完成后回填文件大小等字段。
     *
     * @param  array<string, mixed>  $data  已校验参数：oss_upload_id、video_id
     * @return array<string, mixed>
     */
    public function uploadComplete(array $data): array
    {
        $video = Video::find($data['video_id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $uploadId = $data['oss_upload_id'];
        $partsDir = 'video/parts/' . $uploadId;
        $disk = Storage::disk('oss');

        // 收集已上传分片，按分片序号排序
        if (! $disk->exists($partsDir)) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED, '上传分片不存在，请重新上传');
        }

        $partFiles = collect($disk->files($partsDir))
            ->filter(fn (string $path) => (bool) preg_match('/part_\d+$/', $path))
            ->sortBy(fn (string $path) => (int) substr($path, strrpos($path, '_') + 1))
            ->values();

        if ($partFiles->isEmpty()) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED, '上传分片不存在，请重新上传');
        }

        // 合并分片到最终存储路径（OSS 无本地路径，先落盘临时文件再上传）
        $ossPath = $video->oss_path ?: 'video/' . date('Y/m') . '/' . $uploadId . '_' . $this->sanitizeFileName($video->title . '.mp4');

        $tmpPath = tempnam(sys_get_temp_dir(), 'video_merge_');
        $targetStream = fopen($tmpPath, 'wb');

        foreach ($partFiles as $partFile) {
            $partStream = $disk->readStream($partFile);
            stream_copy_to_stream($partStream, $targetStream);
            fclose($partStream);
        }

        fclose($targetStream);

        $saved = $disk->put($ossPath, fopen($tmpPath, 'rb'));
        @unlink($tmpPath);

        if (! $saved) {
            throw new BusinessException(ResponseCode::FILE_UPLOAD_FAILED);
        }

        $fileSize = (int) $disk->size($ossPath);

        // 清理分片临时目录
        $disk->deleteDirectory($partsDir);

        // 本地模拟：无真实转码服务，合并完成即视为转码完成；
        // 真实环境由转码服务异步回调更新 transcode_status，并回填时长与封面
        $video->update([
            'video_url' => $disk->url($ossPath),
            'oss_path' => $ossPath,
            'file_size' => $fileSize,
            'transcode_status' => 2,
        ]);

        Log::channel('business')->info('视频上传完成', [
            'admin_id' => (int) Auth::guard('admin')->id(),
            'video_id' => $video->id,
            'oss_upload_id' => $uploadId,
            'file_size' => $fileSize,
        ]);

        return [
            'video_id' => (int) $video->id,
            'video_url' => $video->video_url,
            'transcode_status' => (int) $video->transcode_status,
        ];
    }

    /**
     * 校验分类是否存在
     */
    private function ensureCategoryExists(int $categoryId): void
    {
        if (! Category::query()->whereKey($categoryId)->exists()) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '分类不存在');
        }
    }

    /**
     * 解析视频归属课程：传了 topic_id 校验存在；未传时挂到「默认课程」（不存在则创建）。
     *
     * 接口文档 2.2.3 未包含课程参数，但 video.topic_id 为非空外键——此处做服务端兜底，
     * 待团队确认文档补充课程选择或调整库约束后可移除默认逻辑。
     */
    private function resolveTopicId(mixed $topicId, ?int $fallback = null): int
    {
        if (! empty($topicId)) {
            $topicId = (int) $topicId;

            if (! Topic::query()->whereKey($topicId)->exists()) {
                throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '课程不存在');
            }

            return $topicId;
        }

        // 编辑场景未传课程参数时保持原归属
        if ($fallback !== null && $fallback > 0) {
            return $fallback;
        }

        /** @var Topic $topic */
        $topic = Topic::withTrashed()->firstOrCreate(
            ['name' => '默认课程'],
            // cover_url/intro 库里 NOT NULL 无默认值，兜底空串
            ['cover_url' => '', 'intro' => '', 'status' => 1, 'sort_order' => 0]
        );

        if ($topic->trashed()) {
            $topic->restore();
        }

        return (int) $topic->id;
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
     * 清理文件名中的非法字符
     */
    private function sanitizeFileName(string $name): string
    {
        $name = basename($name);

        $clean = trim((string) preg_replace('/[^\w\x{4e00}-\x{9fa5}\.\-]/u', '_', $name));

        return $clean !== '' ? $clean : 'video.mp4';
    }
}
