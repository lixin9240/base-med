<?php

namespace App\Services\Wjc\Admin;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 管理员认证服务
 *
 * 只做业务逻辑：登录校验、获取当前管理员、登出、找回密码。
 */
class AuthService
{
    /**
     * 验证码有效期（分钟）
     */
    private const CODE_EXPIRE_MINUTES = 10;

    /**
     * 验证码发送防刷间隔（秒）
     */
    private const CODE_RESEND_SECONDS = 60;
    /**
     * 管理员登录
     *
     * @param array $data 已校验参数：username、password
     * @return array token 与 admin_info
     */
    public function login(array $data): array
    {
        $admin = AdminUser::query()
            ->where('username', $data['username'])
            ->first();

        // 账号不存在（防止用户名枚举，统一提示账号或密码错误）
        if (! $admin) {
            throw new BusinessException(
                ResponseCode::PASSWORD_ERROR,
                '账号或密码错误'
            );
        }

        // 账号被禁用
        if ($admin->status !== 1) {
            throw new BusinessException(ResponseCode::ACCOUNT_DISABLED);
        }

        // 密码校验
        if (! Hash::check($data['password'], $admin->password_hash)) {
            throw new BusinessException(
                ResponseCode::PASSWORD_ERROR,
                '账号或密码错误'
            );
        }

        // 更新最后登录时间
        $admin->update([
            'last_login_at' => now(),
        ]);

        // 签发 JWT token
        $token = auth('admin')->login($admin);

        Log::channel('business')->info('管理员登录成功', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'ip' => request()->ip(),
        ]);

        return [
            'token' => $token,
            'admin_info' => $this->adminInfo($admin),
        ];
    }

    /**
     * 获取当前登录管理员信息
     */
    public function profile(AdminUser $admin): array
    {
        return $this->adminInfo($admin);
    }

    /**
     * 管理员登出（JWT 加入黑名单失效）
     */
    public function logout(AdminUser $admin): void
    {
        try {
            auth('admin')->logout();
        } catch (\Throwable $e) {
            // token 已过期/无 token 时无需处理
        }

        Log::channel('business')->info('管理员登出', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
        ]);
    }

    /**
     * 发送重置密码验证码
     *
     * @param array $data 已校验参数：username
     * @return array expire_minutes 验证码有效期
     */
    public function sendResetCode(array $data): array
    {
        $admin = AdminUser::query()
            ->where('username', $data['username'])
            ->first();

        // 账号不存在或未绑定邮箱：不暴露信息，统一按成功返回
        if (! $admin || ! $admin->email) {
            Log::channel('business')->info('找回密码：账号不存在或未绑定邮箱', [
                'username' => $data['username'],
            ]);

            return ['expire_minutes' => self::CODE_EXPIRE_MINUTES];
        }

        // 60 秒内不可重复发送（防刷）
        if (Cache::has($this->resetLockKey($admin->username))) {
            throw new BusinessException(ResponseCode::COMMON_TOO_MANY_REQUESTS);
        }

        // 生成 6 位数字验证码并缓存
        $code = (string) random_int(100000, 999999);

        Cache::put(
            $this->resetCodeKey($admin->username),
            $code,
            now()->addMinutes(self::CODE_EXPIRE_MINUTES)
        );
        Cache::put(
            $this->resetLockKey($admin->username),
            true,
            now()->addSeconds(self::CODE_RESEND_SECONDS)
        );

        // 发送 HTML 邮件（QQ 邮箱 SMTP）
        try {
            Mail::html(
                view('emails.reset-code', [
                    'code' => $code,
                    'expireMinutes' => self::CODE_EXPIRE_MINUTES,
                ])->render(),
                function ($message) use ($admin) {
                    /** @var \Illuminate\Mail\Message $message */
                    $message->to($admin->email)->subject('【基层医生DR平台】重置密码验证码');
                }
            );
        } catch (\Throwable $e) {
            Log::channel('exception')->error('重置密码邮件发送失败', [
                'admin_id' => $admin->id,
                'username' => $admin->username,
                'message' => $e->getMessage(),
            ]);

            throw new BusinessException(ResponseCode::MAIL_SEND_FAILED);
        }

        Log::channel('business')->info('重置密码验证码已发送', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
            'email' => $admin->email,
        ]);

        return ['expire_minutes' => self::CODE_EXPIRE_MINUTES];
    }

    /**
     * 重置密码
     *
     * @param array $data 已校验参数：username、code、password
     */
    public function resetPassword(array $data): void
    {
        $admin = AdminUser::query()
            ->where('username', $data['username'])
            ->first();

        if (! $admin) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND);
        }

        // 校验验证码
        $cacheCode = Cache::get($this->resetCodeKey($admin->username));

        if (! $cacheCode || ! hash_equals((string) $cacheCode, $data['code'])) {
            throw new BusinessException(
                ResponseCode::PARAM_ERROR,
                '验证码错误或已过期'
            );
        }

        // 更新密码并立即作废验证码
        $admin->update([
            'password_hash' => Hash::make($data['password']),
        ]);

        Cache::forget($this->resetCodeKey($admin->username));
        Cache::forget($this->resetLockKey($admin->username));

        Log::channel('business')->info('管理员重置密码成功', [
            'admin_id' => $admin->id,
            'username' => $admin->username,
        ]);
    }

    /**
     * 组装对外暴露的管理员信息
     */
    private function adminInfo(AdminUser $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'username' => $admin->username,
            'name' => $admin->name,
            'status' => (int) $admin->status,
        ];
    }

    /**
     * 重置验证码缓存 key
     */
    private function resetCodeKey(string $username): string
    {
        return "admin_reset_code:{$username}";
    }

    /**
     * 验证码发送防刷缓存 key
     */
    private function resetLockKey(string $username): string
    {
        return "admin_reset_lock:{$username}";
    }
}
