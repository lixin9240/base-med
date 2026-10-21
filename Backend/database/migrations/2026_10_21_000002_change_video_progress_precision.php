<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 观看进度精确到 0.1%：秒数保留 2 位小数、百分比保留 1 位小数
        DB::statement("ALTER TABLE video_progress MODIFY watched_seconds DECIMAL(9,2) NOT NULL COMMENT '已观看秒数（保留2位小数，支撑0.1%进度粒度）'");
        DB::statement("ALTER TABLE video_progress MODIFY progress_percent DECIMAL(5,1) NOT NULL COMMENT '进度百分比（精确到0.1%）'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE video_progress MODIFY watched_seconds INT NOT NULL');
        DB::statement('ALTER TABLE video_progress MODIFY progress_percent TINYINT UNSIGNED NOT NULL');
    }
};
