<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 用户端改为免登录、点击视频直接进入：分享链接允许为空，新增直接关联的视频ID
        DB::statement('ALTER TABLE visit_record MODIFY share_link_id BIGINT UNSIGNED NULL');

        Schema::table('visit_record', function (Blueprint $table) {
            $table->unsignedBigInteger('video_id')->nullable()->after('share_link_id')
                ->comment('直接点击视频进入时关联的视频ID（video）；为空表示历史分享链接进入');
            $table->foreign('video_id')->references('id')->on('video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_record', function (Blueprint $table) {
            $table->dropForeign(['video_id']);
            $table->dropColumn('video_id');
        });

        DB::statement('ALTER TABLE visit_record MODIFY share_link_id BIGINT UNSIGNED NOT NULL');
    }
};
