<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 分类种子数据
 *
 * 与 category 表注释预设一致：急救技能 / 慢病管理 / 影像检查 / 妇幼保健。
 * 使用 firstOrCreate 幂等，可重复执行不产生重复数据。
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '急救技能', 'sort_order' => 1],
            ['name' => '慢病管理', 'sort_order' => 2],
            ['name' => '影像检查', 'sort_order' => 3],
            ['name' => '妇幼保健', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'icon' => '',
                    'sort_order' => $category['sort_order'],
                    'status' => 1,
                ]
            );
        }
    }
}
