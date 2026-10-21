<?php

namespace App\Services\Gyz;

use App\Models\OperationLog;
use Illuminate\Support\Facades\Auth;

/**
 * 操作日志服务
 *
 * 只做业务逻辑：操作留痕写入、日志列表查询。
 */
class OperationLogService
{
    /**
     * 写入操作留痕
     *
     * @param  string  $module  模块：video / question / exam / category / share
     * @param  string  $action  动作：create / update / delete / import / export / generate_link
     * @param  int|null  $targetId  目标记录 ID
     * @param  array<mixed>  $detail  操作详情（JSON）
     */
    public function record(string $module, string $action, ?int $targetId = null, array $detail = []): void
    {
        OperationLog::create([
            'admin_id' => Auth::guard('admin')->id(),
            'module' => $module,
            'action' => $action,
            'target_id' => $targetId,
            'detail' => empty($detail) ? null : json_encode($detail, JSON_UNESCAPED_UNICODE),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * 2.9.1 操作日志列表
     *
     * @param  array<string, mixed>  $params  已校验参数：page、page_size、module、action、admin_id
     * @return array{list: array<int, array<string, mixed>>, total: int, page: int, page_size: int}
     */
    public function list(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $paginator = OperationLog::query()
            ->with('admin:id,name')
            ->when(! empty($params['module']), fn ($query) => $query->where('module', $params['module']))
            ->when(! empty($params['action']), fn ($query) => $query->where('action', $params['action']))
            ->when(! empty($params['admin_id']), fn ($query) => $query->where('admin_id', (int) $params['admin_id']))
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        $list = $paginator->getCollection()->map(fn (OperationLog $log) => [
            'id' => (int) $log->id,
            'admin_id' => $log->admin_id !== null ? (int) $log->admin_id : null,
            'admin_name' => $log->admin->name ?? null,
            'module' => $log->module,
            'action' => $log->action,
            'target_id' => $log->target_id !== null ? (int) $log->target_id : null,
            'detail' => $log->detail,
            'ip' => $log->ip,
            'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
        ])->values()->all();

        return [
            'list' => $list,
            'total' => $paginator->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
