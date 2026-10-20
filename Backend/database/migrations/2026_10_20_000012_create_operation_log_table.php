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
        Schema::create('operation_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('操作人ID');
            $table->string('module', 32)->comment('模块：video/question/exam/category/share');
            $table->string('action', 32)->comment('操作：create/update/delete/import/export/generate_link');
            $table->unsignedBigInteger('target_id')->nullable()->comment('操作对象ID');
            $table->text('detail')->nullable()->comment('操作详情（JSON）');
            $table->string('ip', 32)->nullable()->comment('IP地址');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('admin_id')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_log');
    }
};
