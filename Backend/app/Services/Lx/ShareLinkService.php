<?php

namespace App\Services\Lx;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\VideoShareLink;
use App\Models\VisitRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShareLinkService
{
    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 生成访问链接
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        $token = $this->generateToken();

        $expireType = $params['expire_type'];
        $expireAt = $this->resolveExpireAt($expireType, $params['expire_at'] ?? null);

        $link = VideoShareLink::create([
            'video_id' => $params['video_id'],
            'org_id' => $params['org_id'] ?? null,
            'token' => $token,
            'expire_type' => $expireType,
            'expire_at' => $expireAt,
            'visit_count' => 0,
            'status' => 1,
        ]);

        $this->operationLogService->record(
            module: 'share',
            action: 'generate_link',
            targetId: $link->id,
            detail: ['video_id' => $link->video_id, 'expire_type' => $expireType],
        );

        return [
            'id' => $link->id,
            'video_id' => $link->video_id,
            'token' => $token,
            'url' => $this->buildUrl($token),
            'expire_type' => $expireType,
            'expire_at' => $expireAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 链接列表
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = VideoShareLink::query()->with('video:id,title');

        if (! empty($params['video_id'])) {
            $query->where('video_id', (int) $params['video_id']);
        }

        if (isset($params['status'])) {
            $query->where('status', (int) $params['status']);
        }

        $paginator = $query->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (VideoShareLink $link) => [
                'id' => $link->id,
                'video_id' => $link->video_id,
                'video_title' => $link->video->title ?? null,
                'org_id' => $link->org_id,
                'token' => $link->token,
                'url' => $this->buildUrl($link->token),
                'expire_type' => $link->expire_type,
                'expire_at' => $link->expire_at?->format('Y-m-d H:i:s'),
                'visit_count' => $link->visit_count,
                'status' => $link->status,
                'created_at' => $link->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 启用/失效链接
     *
     * @param  array<string, mixed>  $params
     * @return void
     */
    public function updateStatus(array $params): void
    {
        $link = VideoShareLink::find($params['id']);

        if (! $link) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '链接不存在');
        }

        $link->update(['status' => (int) $params['status']]);
    }

    /**
     * 删除链接（软删除，访问记录与答题数据保留）
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void
    {
        $link = VideoShareLink::find($id);

        if (! $link) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '链接不存在');
        }

        $link->delete();
    }

    /**
     * 访问记录列表
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function visitRecordList(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = VisitRecord::query()->with('shareLink.video:id,title');

        if (! empty($params['link_id'])) {
            $query->where('share_link_id', (int) $params['link_id']);
        }

        if (! empty($params['video_id'])) {
            $query->whereHas('shareLink', fn ($q) => $q->where('video_id', (int) $params['video_id']));
        }

        if (! empty($params['dr_id'])) {
            $query->where('dr_id', $params['dr_id']);
        }

        $paginator = $query->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (VisitRecord $record) => [
                'id' => $record->id,
                'dr_id' => $record->dr_id,
                'share_link_id' => $record->share_link_id,
                'video_title' => $record->shareLink->video->title ?? null,
                'entered_at' => $record->entered_at?->format('Y-m-d H:i:s'),
                'created_at' => $record->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 计算过期时间
     */
    private function resolveExpireAt(string $type, ?string $customAt): ?Carbon
    {
        return match ($type) {
            'permanent' => null,
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            'custom' => Carbon::parse($customAt),
        };
    }

    /**
     * 生成全局唯一 token
     */
    private function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(16));
        } while (VideoShareLink::where('token', $token)->exists());

        return $token;
    }

    /**
     * 拼接 H5 访问链接
     */
    private function buildUrl(string $token): string
    {
        return rtrim((string) config('app.url'), '/') . '/h5/v?token=' . $token;
    }
}