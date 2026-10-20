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
        Schema::create('exam_record', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('关联匿名访问记录ID（visit_record）');
            $table->string('dr_id', 32)->comment('匿名编号快照');
            $table->unsignedBigInteger('video_id')->comment('关联内容ID');
            $table->integer('attempt_no')->default(1)->comment('第几次作答');
            $table->tinyInteger('pretest_correct')->default(0)->comment('前测答对题数');
            $table->tinyInteger('posttest_correct')->default(0)->comment('后测答对题数');
            $table->decimal('pretest_score', 5, 2)->default(0)->comment('前测得分');
            $table->decimal('posttest_score', 5, 2)->default(0)->comment('后测得分');
            $table->tinyInteger('accuracy_rate')->default(0)->comment('正确率百分比');
            $table->string('result_status', 16)->comment('结果：pass通过 fail未通过');
            $table->integer('total_duration')->default(0)->comment('总用时（秒）');
            $table->dateTime('submit_at')->comment('提交时间');
            $table->string('ip', 32)->nullable()->comment('IP地址');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('visit_record');
            $table->foreign('video_id')->references('id')->on('video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_record');
    }
};
