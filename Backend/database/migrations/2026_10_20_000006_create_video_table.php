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
        Schema::create('video', function (Blueprint $table) {
            $table->id();
            $table->string('title', 60)->comment('标题');
            $table->unsignedInteger('category_id')->comment('分类ID，关联category表');
            $table->unsignedInteger('topic_id')->comment('课程ID，关联topic表');
            $table->string('speaker', 32)->comment('主讲人姓名');
            $table->string('speaker_title', 32)->comment('主讲人职称：主任医师/副主任医师/主治医师');
            $table->string('speaker_org', 64)->comment('主讲人单位');
            $table->string('brief_intro', 120)->comment('一句话简介');
            $table->text('text_key_points')->comment('配套图文内容（富文本）');
            $table->tinyInteger('source_type')->comment('视频来源：1填写链接 2本地上传');
            $table->string('video_url', 256)->nullable()->comment('视频链接地址');
            $table->string('oss_path', 256)->nullable()->comment('OSS存储路径（本地上传时）');
            $table->string('oss_upload_id', 128)->nullable()->comment('OSS分片上传UploadId（断点续传用）');
            $table->string('cover_url', 256)->nullable()->comment('封面图URL（上传视频自动生成）');
            $table->integer('duration')->default(0)->comment('视频时长（秒）');
            $table->bigInteger('file_size')->default(0)->comment('文件大小（字节）');
            $table->tinyInteger('transcode_status')->default(0)->comment('转码状态：0待转码 1转码中 2已完成 3失败');
            $table->integer('sort_order')->default(0)->comment('排序号');
            $table->tinyInteger('status')->default(0)->comment('上架状态：0已下架 1已上架');
            $table->unsignedBigInteger('created_by')->comment('创建人ID');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('category');
            $table->foreign('topic_id')->references('id')->on('topic');
            $table->foreign('created_by')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video');
    }
};
