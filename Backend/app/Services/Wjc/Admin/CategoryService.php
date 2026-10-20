<?php

namespace App\Services\Wjc\Admin;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\Category;
use App\Models\Video;
use Illuminate\Support\Facades\Log;

/**
 * 分类管理服务
 *
 * 只做业务逻辑：分类列表、新增、编辑、删除。
 */
class CategoryService
{
    /**
     * 分类列表（全量、不分页，按 sort_order 排序）
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $categories->map(function (Category $category) {
            return [
                'id' => (int) $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'sort_order' => (int) $category->sort_order,
                'status' => (int) $category->status,
            ];
        })->values()->all();
    }

    /**
     * 新增分类
     *
     * @param  array<string, mixed>  $data  已校验参数：name、icon、sort_order、status
     * @return int 分类 ID
     */
    public function create(array $data): int
    {
        $category = Category::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);

        Log::channel('business')->info('新增分类', [
            'admin_id' => auth('admin')->id(),
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return (int) $category->id;
    }

    /**
     * 编辑分类
     *
     * @param  array<string, mixed>  $data  已校验参数：id、name、icon、sort_order、status
     */
    public function update(array $data): void
    {
        $category = Category::find($data['id']);

        if (! $category) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '分类不存在');
        }

        $category->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? $category->icon,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'status' => $data['status'] ?? $category->status,
        ]);

        Log::channel('business')->info('编辑分类', [
            'admin_id' => auth('admin')->id(),
            'category_id' => $category->id,
            'name' => $category->name,
        ]);
    }

    /**
     * 删除分类（软删除；存在关联视频时不允许删除）
     *
     * @param  array<string, mixed>  $data  已校验参数：id
     */
    public function delete(array $data): void
    {
        $category = Category::find($data['id']);

        if (! $category) {
            throw new BusinessException(ResponseCode::DATA_NOT_FOUND, '分类不存在');
        }

        // 存在关联视频时不允许删除
        $videoCount = Video::query()
            ->where('category_id', $category->id)
            ->count();

        if ($videoCount > 0) {
            throw new BusinessException(
                ResponseCode::COMMON_OPERATION_FAILED,
                '该分类下存在关联视频，无法删除'
            );
        }

        $category->delete();

        Log::channel('business')->info('删除分类', [
            'admin_id' => auth('admin')->id(),
            'category_id' => $category->id,
            'name' => $category->name,
        ]);
    }
}
