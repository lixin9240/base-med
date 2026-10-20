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
        Schema::create('import_task', function (Blueprint $table) {
            $table->id();
            $table->string('task_no', 32)->unique()->comment('任务编号，如imp_20261019_001');
            $table->string('file_name', 128)->comment('文件名');
            $table->string('file_path', 256)->comment('文件存储路径');
            $table->integer('total_rows')->default(0)->comment('总行数');
            $table->integer('valid_rows')->default(0)->comment('有效行数');
            $table->integer('error_rows')->default(0)->comment('错误行数');
            $table->tinyInteger('status')->default(0)->comment('状态：0待校验 1校验中 2待确认 3导入中 4已完成 5失败');
            $table->json('error_report')->nullable()->comment('错误详情：[{"row":15,"sheet":"题目","message":"..."}]');
            $table->unsignedBigInteger('created_by')->comment('创建人ID');
            $table->timestamps();
            $table->dateTime('completed_at')->nullable()->comment('完成时间');

            $table->foreign('created_by')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_task');
    }
};
