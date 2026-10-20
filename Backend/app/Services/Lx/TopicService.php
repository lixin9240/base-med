<?php

namespace App\Services\Lx;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Topic;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TopicService
{
    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 课程列表（分页，支持关键词与状态筛选）
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = Topic::query();

        if (! empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $paginator = $query->orderBy('sort_order')->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Topic $topic) => [
                'id' => (int) $topic->id,
                'name' => $topic->name,
                'cover_url' => $topic->cover_url,
                'intro' => $topic->intro,
                'sort_order' => (int) $topic->sort_order,
                'status' => (int) $topic->status,
                'created_at' => $topic->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 新增课程
     *
     * @param  array<string, mixed>  $data
     * @return int 课程 ID
     */
    public function create(array $data): int
    {
        $topic = Topic::create([
            'name' => $data['name'],
            'cover_url' => $data['cover_url'] ?? '',
            'intro' => $data['intro'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => (int) ($data['status'] ?? 1),
        ]);

        $this->operationLogService->record('topic', 'create', (int) $topic->id, [
            'name' => $topic->name,
        ]);

        return (int) $topic->id;
    }

    /**
     * 编辑课程
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        $topic = Topic::find($data['id']);

        if (! $topic) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '课程不存在');
        }

        $topic->update([
            'name' => $data['name'] ?? $topic->name,
            'cover_url' => $data['cover_url'] ?? $topic->cover_url,
            'intro' => $data['intro'] ?? $topic->intro,
            'sort_order' => $data['sort_order'] ?? $topic->sort_order,
            'status' => (int) ($data['status'] ?? $topic->status),
        ]);

        $this->operationLogService->record('topic', 'update', (int) $topic->id, [
            'name' => $topic->name,
        ]);
    }

    /**
     * 删除课程（软删除，存在关联视频时禁止删除）
     *
     * @param  int  $id
     */
    public function delete(int $id): void
    {
        $topic = Topic::find($id);

        if (! $topic) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '课程不存在');
        }

        if (Video::where('topic_id', $id)->exists()) {
            throw new BusinessException(ResponseCode::DATA_RELATION_EXISTS, '该课程下存在关联视频，无法删除');
        }

        $topic->delete();

        $this->operationLogService->record('topic', 'delete', (int) $topic->id, [
            'name' => $topic->name,
        ]);
    }
}