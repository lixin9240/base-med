<?php

namespace App\Services\Fmy;

use App\Models\VisitRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrService
{
    public function __construct(
        private readonly VideoService $videoService
    ) {
    }

    /**
     * 获取匿名 DR 编号（用户每次点击一条视频数据时生成动态ID）
     *
     * @return array{dr_id: string}
     */
    public function create(int $videoId): array
    {
        $video = $this->videoService->onShelfVideo($videoId);

        return DB::transaction(function () use ($video) {
            $drId = $this->issueDrId();

            VisitRecord::create([
                'dr_id' => $drId,
                'video_id' => $video->id,
                'entered_at' => now(),
            ]);

            Log::channel('business')->info('匿名访问记录创建', [
                'dr_id' => $drId,
                'video_id' => $video->id,
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
