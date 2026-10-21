<?php

namespace App\Services\Gyz;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Video;
use App\Models\VideoShareLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 视频分享链接服务
 *
 * 只做业务逻辑：生成/重新生成、详情、撤回。链接用于发布到公众号，用户无需登录即可观看视频并答题。
 * 时效仅支持 1/3/5/7 天（对应 expire_type 存储为 1d/3d/5d/7d）。
 */
class VideoShareLinkService
{
    /**
     * H5 学习页路径（拼接在 OSS 域名之后）
     */
    private const LEARN_PATH = '/mobile/learn';

    /**
     * 允许的时效天数
     */
    private const EXPIRE_DAYS = [1, 3, 5, 7];

    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 生成 / 重新生成分享链接
     *
     * 命中「自动失效旧链接」：同一视频已有生效链接时，先生效旧链接再创建新链接。
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id、expire_days
     * @return array<string, mixed>
     */
    public function generate(array $data): array
    {
        $video = Video::find((int) $data['video_id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $days = (int) $data['expire_days'];

        if (! in_array($days, self::EXPIRE_DAYS, true)) {
            throw new BusinessException(ResponseCode::PARAM_INVALID, '时效仅支持 1/3/5/7 天');
        }

        // 自动失效旧链接（含历史过期但仍标记启用的记录，一并置为失效）
        VideoShareLink::query()
            ->where('video_id', $video->id)
            ->where('status', 1)
            ->update(['status' => 0]);

        $link = VideoShareLink::create([
            'video_id' => $video->id,
            'org_id' => null,
            'token' => $this->uniqueToken(),
            'expire_type' => $days . 'd',
            'expire_at' => now()->addDays($days),
            'visit_count' => 0,
            'status' => 1,
            'created_by' => (int) Auth::guard('admin')->id(),
        ]);

        $this->operationLogService->record('share', 'generate_link', (int) $link->id, [
            'video_id' => $video->id,
            'title' => $video->title,
            'expire_type' => $link->expire_type,
        ]);

        return $this->formatLink($link);
    }

    /**
     * 获取当前生效链接详情
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id
     * @return array<string, mixed>|null  无生效链接时返回 null
     */
    public function detail(array $data): ?array
    {
        $video = Video::find((int) $data['video_id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $link = $this->activeLink($video->id);

        return $link ? $this->formatLink($link) : null;
    }

    /**
     * 撤回生效链接
     *
     * @param  array<string, mixed>  $data  已校验参数：video_id
     */
    public function revoke(array $data): void
    {
        $video = Video::find((int) $data['video_id']);

        if (! $video) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '视频不存在');
        }

        $link = $this->activeLink($video->id);

        if (! $link) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '该视频暂无生效中的链接');
        }

        $link->update(['status' => 0]);

        $this->operationLogService->record('share', 'revoke', (int) $link->id, [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);
    }

    /**
     * 当前生效链接（启用且未过期）
     */
    private function activeLink(int $videoId): ?VideoShareLink
    {
        return VideoShareLink::query()
            ->where('video_id', $videoId)
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('expire_at')->orWhere('expire_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    /**
     * 生成唯一 token（避免与历史 token 冲突）
     */
    private function uniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (VideoShareLink::query()->where('token', $token)->exists());

        return $token;
    }

    /**
     * 拼装完整 H5 链接
     */
    private function buildUrl(VideoShareLink $link): string
    {
        $base = (string) config('filesystems.disks.oss.url', '');

        return rtrim($base, '/') . self::LEARN_PATH . '?video_id=' . $link->video_id . '&share=' . $link->token;
    }

    /**
     * 统一对外结构
     *
     * @return array<string, mixed>
     */
    private function formatLink(VideoShareLink $link): array
    {
        return [
            'id' => (int) $link->id,
            'video_id' => (int) $link->video_id,
            'url' => $this->buildUrl($link),
            'token' => $link->token,
            'expire_type' => $link->expire_type,
            'expire_at' => $link->expire_at?->format('Y-m-d H:i:s'),
            'visit_count' => (int) $link->visit_count,
            'status' => (int) $link->status,
            'created_at' => $link->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}