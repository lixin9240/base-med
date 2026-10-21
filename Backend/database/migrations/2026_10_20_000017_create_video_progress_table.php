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
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联匿名访问记录ID（visit_record），同exam_record.user_id');
            $table->string('dr_id', 32)->comment('匿名编号快照');
            $table->unsignedBigInteger('video_id')->comment('关联内容ID（video）');
            $table->integer('watched_seconds')->default(0)->comment('已观看时长（秒），取心跳上报最大值');
            $table->integer('duration')->default(0)->comment('视频总时长快照（秒）');
            $table->unsignedTinyInteger('progress_percent')->default(0)->comment('观看进度百分比（0-100，后端计算）');
            $table->tinyInteger('is_unlocked')->default(0)->comment('是否达到观看门槛可提交后测：0未达成 1已达成');
            $table->dateTime('last_watched_at')->nullable()->comment('最近一次观看心跳时间');
            $table->timestamps();

            $table->unique(['user_id', 'video_id'], 'uniq_progress_user_video');
            $table->index('dr_id', 'idx_progress_dr_id');
            $table->foreign('user_id')->references('id')->on('visit_record');
            $table->foreign('video_id')->references('id')->on('video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};
