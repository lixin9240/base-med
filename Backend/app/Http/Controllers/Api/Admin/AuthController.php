<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 管理端·管理员登录模块（对应接口文档 2.1）
 *
 * 说明：本类为响应模板样板，仅演示 data 组织结构与统一响应封装，
 * 具体的参数校验、业务处理后续由 FormRequest / Service 承接。
 */
class AuthController extends Controller
{
    /**
     * 2.1.1 管理员登录
     * POST /api/admin/auth/login
     */
    public function login(): JsonResponse
    {
        return $this->success(data: [
            'token' => 'eyJ0eXAi...',
            'admin_info' => [
                'id' => 1,
                'username' => 'admin',
                'name' => '系统管理员',
                'status' => 1,
            ],
        ]);
    }

    /**
     * 2.1.2 获取当前登录管理员信息
     * GET /api/admin/auth/profile
     */
    public function profile(): JsonResponse
    {
        return $this->success(data: [
            'id' => 1,
            'username' => 'admin',
            'name' => '系统管理员',
            'status' => 1,
        ]);
    }

    /**
     * 2.1.3 管理员登出
     * POST /api/admin/auth/logout
     */
    public function logout(): JsonResponse
    {
        return $this->success(data: [
            'success' => true,
        ]);
    }
}
