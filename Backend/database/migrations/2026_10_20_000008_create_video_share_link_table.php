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
        Schema::create('video_share_link', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('video_id')->comment('关联内容ID');
            $table->unsignedInteger('org_id')->nullable()->comment('归属机构ID（关联organization）');
            $table->string('token', 64)->unique()->comment('访问令牌，拼接在h5链接后');
            $table->string('expire_type', 16)->comment('时效类型：permanent永久 7d 30d custom');
            $table->dateTime('expire_at')->nullable()->comment('过期时间（permanent为NULL）');
            $table->integer('visit_count')->default(0)->comment('访问次数');
            $table->tinyInteger('status')->default(1)->comment('状态：0失效 1启用');
            $table->unsignedBigInteger('created_by')->comment('创建人ID');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('video_id')->references('id')->on('video');
            $table->foreign('org_id')->references('id')->on('organization')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_share_link');
    }
};
