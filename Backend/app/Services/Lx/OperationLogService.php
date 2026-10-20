<?php

namespace App\Services\Lx;

use App\Models\OperationLog;

class OperationLogService
{
    /**
     * 写入操作留痕
     *
     * @param  string  $module  模块：video / question / exam / category / share
     * @param  string  $action  动作：create / update / delete / import / export / generate_link
     * @param  int|null  $targetId  目标记录 ID
     * @param  array<mixed>  $detail  操作详情（JSON）
     * @return void
     */
    public function record(string $module, string $action, ?int $targetId = null, array $detail = []): void
    {
        $adminId = auth()->id(); // 登录模块未就绪，当前为 null，接入后自动填充

        OperationLog::create([
            'admin_id' => $adminId,
            'module' => $module,
            'action' => $action,
            'target_id' => $targetId,
            'detail' => empty($detail) ? null : json_encode($detail, JSON_UNESCAPED_UNICODE),
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}