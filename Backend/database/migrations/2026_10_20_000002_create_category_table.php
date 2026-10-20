<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32)->comment('分类名称：急救技能/慢病管理/影像检查/妇幼保健');
            $table->string('icon', 128)->comment('分类图标URL');
            $table->integer('sort_order')->default(0)->comment('排序号');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用 1启用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
