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
        Schema::table('import_task', function (Blueprint $table) {
            $table->unsignedBigInteger('video_id')->nullable()->after('file_path')->comment('题目导入任务的目标视频ID（批量导入任务为空）');

            $table->index('video_id', 'idx_import_task_video_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_task', function (Blueprint $table) {
            $table->dropIndex('idx_import_task_video_id');
            $table->dropColumn('video_id');
        });
    }
};
