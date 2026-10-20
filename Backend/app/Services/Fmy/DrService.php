<?php

namespace App\Services\Fmy;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\VideoShareLink;
use App\Models\VisitRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrService
{
    /**
     * 获取匿名 DR 编号（点击链接进入时签发访问记录）
     *
     * @return array{dr_id: string}
     */
    public function create(string $token): array
    {
        $link = VideoShareLink::query()->where('token', $token)->first();

        if (! $link) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '链接不存在');
        }

        if ((int) $link->status !== 1) {
            throw new BusinessException(ResponseCode::STATUS_NOT_OPERABLE, '链接已失效');
        }

        if ($link->expire_at && $link->expire_at->isPast()) {
            throw new BusinessException(ResponseCode::STATUS_NOT_OPERABLE, '链接已过期');
        }

        return DB::transaction(function () use ($link) {
            $drId = $this->issueDrId();

            VisitRecord::create([
                'dr_id' => $drId,
                'share_link_id' => $link->id,
                'entered_at' => now(),
            ]);

            $link->increment('visit_count');

            Log::channel('business')->info('匿名访问记录创建', [
                'dr_id' => $drId,
                'share_link_id' => $link->id,
                'video_id' => $link->video_id,
            ]);

            return ['dr_id' => $drId];
        });
    }

    /**
     * 顺序签发匿名编号：DR-年份-6位顺序号（如 DR-2026-000017）
     */
    private function issueDrId(): string
    {
        $prefix = 'DR-' . now()->format('Y') . '-';

        $latest = VisitRecord::query()
            ->where('dr_id', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('dr_id');

        $seq = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;

        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
