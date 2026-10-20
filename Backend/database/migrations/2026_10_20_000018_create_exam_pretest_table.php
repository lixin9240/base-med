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
        Schema::create('exam_pretest', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联匿名访问记录ID（visit_record），同exam_record.user_id');
            $table->string('dr_id', 32)->comment('匿名编号快照');
            $table->unsignedBigInteger('video_id')->comment('关联内容ID（video）');
            $table->json('answers')->comment('学前答题暂存：[{"question_id":101,"user_answer":"C","duration":25}]');
            $table->dateTime('submitted_at')->comment('学前答题提交时间');
            $table->timestamps();

            $table->unique(['user_id', 'video_id'], 'uniq_pretest_user_video');
            $table->index('dr_id', 'idx_pretest_dr_id');
            $table->foreign('user_id')->references('id')->on('visit_record');
            $table->foreign('video_id')->references('id')->on('video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_pretest');
    }
};
