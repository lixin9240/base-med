<?php

namespace App\Http\Controllers\Wjc\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wjc\Admin\AdminUserCreateRequest;
use App\Http\Requests\Wjc\Admin\AdminUserDeleteRequest;
use App\Http\Requests\Wjc\Admin\AdminUserListRequest;
use App\Http\Requests\Wjc\Admin\AdminUserUpdateRequest;
use App\Services\Wjc\Admin\AdminUserService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 管理员用户管理控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 AdminUserService。
 */
class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $adminUserService
    ) {
    }

    /**
     * 管理员用户列表
     */
    public function list(AdminUserListRequest $request): JsonResponse
    {
        $data = $this->adminUserService->list($request->validated());

        return $this->successPaginate($data);
    }

    /**
     * 新增管理员
     */
    public function create(AdminUserCreateRequest $request): JsonResponse
    {
        $id = $this->adminUserService->create($request->validated());

        return Result::success('新增成功', ['id' => $id]);
    }

    /**
     * 编辑管理员
     */
    public function update(AdminUserUpdateRequest $request): JsonResponse
    {
        $this->adminUserService->update($request->validated());

        return Result::success('保存成功', ['success' => true]);
    }

    /**
     * 删除管理员
     */
    public function delete(AdminUserDeleteRequest $request): JsonResponse
    {
        $this->adminUserService->delete($request->validated());

        return Result::success('删除成功', ['success' => true]);
    }
}