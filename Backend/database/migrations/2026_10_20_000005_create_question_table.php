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
        Schema::create('question', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->comment('题型：single单选 multiple多选 judge判断');
            $table->string('stem', 300)->comment('题干');
            $table->string('image_url', 256)->nullable()->comment('题目配图URL');
            $table->json('options')->comment('选项数组：[{"key":"A","content":"..."}]');
            $table->tinyInteger('option_count')->comment('选项数量：单选/多选2-6个，判断2个');
            $table->string('correct_answer', 16)->comment('正确答案：单选"A"；多选"A,B,C"；判断"正确"/"错误"');
            $table->string('analysis', 500)->comment('答案解析');
            $table->tinyInteger('difficulty')->default(1)->comment('难度：1简单 2中等 3困难');
            $table->string('knowledge_point', 64)->comment('知识点标签');
            $table->unsignedBigInteger('created_by')->comment('创建人ID');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question');
    }
};
