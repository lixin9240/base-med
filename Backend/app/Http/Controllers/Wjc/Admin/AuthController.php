<?php

namespace App\Http\Controllers\Wjc\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wjc\Admin\ForgotPasswordRequest;
use App\Http\Requests\Wjc\Admin\LoginRequest;
use App\Http\Requests\Wjc\Admin\ResetPasswordRequest;
use App\Models\AdminUser;
use App\Services\Wjc\Admin\AuthService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 管理员认证控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 AuthService。
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * 管理员登录
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        return Result::success('登录成功', $data);
    }

    /**
     * 获取当前登录管理员信息
     */
    public function profile(): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = auth('admin')->user();

        $data = $this->authService->profile($admin);

        return Result::success('获取成功', $data);
    }

    /**
     * 管理员登出
     */
    public function logout(): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = auth('admin')->user();

        $this->authService->logout($admin);

        return Result::success('退出成功', ['success' => true]);
    }

    /**
     * 发送重置密码验证码
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $this->authService->sendResetCode($request->validated());

        return Result::success('验证码已发送', $data);
    }

    /**
     * 重置密码
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return Result::success('密码重置成功', ['success' => true]);
    }
}
