<?php

namespace App\Services\Wjc\Admin;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\AdminUser;
use App\Services\Lx\OperationLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

/**
 * 管理员用户管理服务
 *
 * 只做业务逻辑：列表、新增、编辑、删除。
 */
class AdminUserService
{
    public function __construct(
        private readonly OperationLogService $operationLogService
    ) {
    }

    /**
     * 管理员列表（分页，支持关键词与状态筛选）
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator
     */
    public function list(array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);

        $query = AdminUser::query();

        if (! empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $paginator = $query->orderByDesc('id')->paginate($pageSize, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (AdminUser $admin) => [
                'id' => (int) $admin->id,
                'username' => $admin->username,
                'email' => $admin->email,
                'name' => $admin->name,
                'role' => $admin->role,
                'status' => (int) $admin->status,
                'last_login_at' => $admin->last_login_at?->format('Y-m-d H:i:s'),
                'created_at' => $admin->created_at?->format('Y-m-d H:i:s'),
            ])->values()
        );

        return $paginator;
    }

    /**
     * 新增管理员
     *
     * @param  array<string, mixed>  $data  已校验参数
     * @return int 管理员 ID
     */
    public function create(array $data): int
    {
        if (AdminUser::withTrashed()->where('username', $data['username'])->exists()) {
            throw new BusinessException(ResponseCode::DATA_DUPLICATE, '用户名已存在');
        }

        if (! empty($data['email']) && AdminUser::withTrashed()->where('email', $data['email'])->exists()) {
            throw new BusinessException(ResponseCode::DATA_DUPLICATE, '邮箱已被使用');
        }

        $admin = AdminUser::create([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? 'admin',
            'status' => (int) ($data['status'] ?? 1),
        ]);

        $this->operationLogService->record('admin_user', 'create', (int) $admin->id, [
            'username' => $admin->username,
        ]);

        return (int) $admin->id;
    }

    /**
     * 编辑管理员
     *
     * @param  array<string, mixed>  $data  已校验参数
     */
    public function update(array $data): void
    {
        $admin = AdminUser::find($data['id']);

        if (! $admin) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '用户不存在');
        }

        $currentAdminId = (int) auth('admin')->id();

        // 不能禁用当前登录账号
        if ((int) $admin->id === $currentAdminId && isset($data['status']) && (int) $data['status'] === 0) {
            throw new BusinessException(ResponseCode::COMMON_FORBIDDEN, '不能禁用当前登录账号');
        }

        // 用户名唯一性（排除自身）
        if (isset($data['username']) && $data['username'] !== $admin->username) {
            $exists = AdminUser::withTrashed()
                ->where('username', $data['username'])
                ->where('id', '!=', $admin->id)
                ->exists();

            if ($exists) {
                throw new BusinessException(ResponseCode::DATA_DUPLICATE, '用户名已存在');
            }
        }

        // 邮箱唯一性（排除自身）
        if (! empty($data['email']) && $data['email'] !== $admin->email) {
            $exists = AdminUser::withTrashed()
                ->where('email', $data['email'])
                ->where('id', '!=', $admin->id)
                ->exists();

            if ($exists) {
                throw new BusinessException(ResponseCode::DATA_DUPLICATE, '邮箱已被使用');
            }
        }

        $admin->username = $data['username'] ?? $admin->username;
        $admin->name = $data['name'] ?? $admin->name;
        $admin->email = $data['email'] ?? $admin->email;
        $admin->role = $data['role'] ?? $admin->role;
        $admin->status = (int) ($data['status'] ?? $admin->status);

        if (! empty($data['password'])) {
            $admin->password_hash = Hash::make($data['password']);
        }

        $admin->save();

        $this->operationLogService->record('admin_user', 'update', (int) $admin->id, [
            'username' => $admin->username,
        ]);
    }

    /**
     * 删除管理员（软删除）
     *
     * @param  array<string, mixed>  $data  已校验参数：id
     */
    public function delete(array $data): void
    {
        $admin = AdminUser::find($data['id']);

        if (! $admin) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '用户不存在');
        }

        if ((int) $admin->id === (int) auth('admin')->id()) {
            throw new BusinessException(ResponseCode::COMMON_FORBIDDEN, '不能删除当前登录账号');
        }

        $admin->delete();

        $this->operationLogService->record('admin_user', 'delete', (int) $admin->id, [
            'username' => $admin->username,
        ]);
    }
}