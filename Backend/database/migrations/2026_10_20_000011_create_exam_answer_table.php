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
        Schema::create('exam_answer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_record_id')->comment('关联答题记录ID');
            $table->unsignedBigInteger('question_id')->comment('关联题目ID');
            $table->string('stage', 16)->comment('环节：pretest前测 posttest后测');
            $table->string('user_answer', 32)->nullable()->comment('用户答案');
            $table->tinyInteger('is_correct')->default(0)->comment('是否正确：0错误 1正确');
            $table->decimal('score', 5, 2)->default(0)->comment('该题得分');
            $table->integer('duration')->default(0)->comment('答题用时（秒）');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('exam_record_id')->references('id')->on('exam_record')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answer');
    }
};
