<?php

namespace App\Http\Controllers\Wjc\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wjc\Admin\CategoryCreateRequest;
use App\Http\Requests\Wjc\Admin\CategoryDeleteRequest;
use App\Http\Requests\Wjc\Admin\CategoryUpdateRequest;
use App\Services\Wjc\Admin\CategoryService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 分类管理控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 CategoryService。
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    /**
     * 2.7.1 分类列表
     */
    public function list(): JsonResponse
    {
        $data = $this->categoryService->list();

        return Result::success('获取成功', $data);
    }

    /**
     * 2.7.2 新增分类
     */
    public function create(CategoryCreateRequest $request): JsonResponse
    {
        $id = $this->categoryService->create($request->validated());

        return Result::success('新增成功', ['id' => $id]);
    }

    /**
     * 2.7.3 编辑分类
     */
    public function update(CategoryUpdateRequest $request): JsonResponse
    {
        $this->categoryService->update($request->validated());

        return Result::success('保存成功', ['success' => true]);
    }

    /**
     * 2.7.4 删除分类
     */
    public function delete(CategoryDeleteRequest $request): JsonResponse
    {
        $this->categoryService->delete($request->validated());

        return Result::success('删除成功', ['success' => true]);
    }
}
