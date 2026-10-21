<?php

namespace App\Auth;

use App\Models\AdminUser;
use Illuminate\Auth\EloquentUserProvider;

/**
 * 管理员认证提供者
 *
 * 在 Eloquent 提供者基础上增加状态校验：被禁用（status != 1）的账号
 * 一律视为未认证。JWTGuard 每次请求都通过 retrieveById 从 token 解析用户，
 * 因此禁用后已签发的 token 无需黑名单即同步失效。
 */
class AdminUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $user = parent::retrieveById($identifier);

        if ($user instanceof AdminUser && (int) $user->status !== 1) {
            return null;
        }

        return $user;
    }
}
