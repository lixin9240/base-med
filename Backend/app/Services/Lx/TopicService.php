<?php

namespace App\Services\Lx;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Topic;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TopicService
{
    /**
     * 课程列表
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = Topic::query()->withCount('videos as video_count');

        if (! empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }

        $paginator = $query->orderBy('sort_order')->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'name' => $topic->name,
                'cover_url' => $topic->cover_url,
                'intro' => $topic->intro,
                'sort_order' => $topic->sort_order,
                'status' => $topic->status,
                'video_count' => (int) $topic->video_count,
                'created_at' => $topic->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 新增课程
     *
     * @param  array<string, mixed>  $params
     * @return int
     */
    public function create(array $params): int
    {
        $topic = Topic::create([
            'name' => $params['name'],
            'cover_url' => $params['cover_url'] ?? null,
            'intro' => $params['intro'] ?? null,
            'sort_order' => $params['sort_order'] ?? 0,
            'status' => $params['status'] ?? 1,
        ]);

        return $topic->id;
    }

    /**
     * 编辑课程
     *
     * @param  array<string, mixed>  $params
     * @return int
     */
    public function update(array $params): int
    {
        $topic = Topic::find($params['id']);

        if (! $topic) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '课程不存在');
        }

        $topic->update([
            'name' => $params['name'],
            'cover_url' => $params['cover_url'] ?? null,
            'intro' => $params['intro'] ?? null,
            'sort_order' => $params['sort_order'] ?? 0,
            'status' => $params['status'] ?? 1,
        ]);

        return $topic->id;
    }

    /**
     * 删除课程（软删除，存在关联视频时禁止）
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void
    {
        $topic = Topic::find($id);

        if (! $topic) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '课程不存在');
        }

        if (Video::where('topic_id', $id)->exists()) {
            throw new BusinessException(ResponseCode::DATA_RELATION_EXISTS, '课程下存在关联视频，不允许删除');
        }

        $topic->delete();
    }
}