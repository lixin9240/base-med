<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新增「是否医护人员」固定题答案字段（该题不计入答题正确率）
     */
    public function up(): void
    {
        Schema::table('exam_record', function (Blueprint $table) {
            $table->tinyInteger('is_medical_staff')
                ->nullable()
                ->after('attempt_no')
                ->comment('是否医护人员：1-是，0-否，NULL-未采集');
        });

        Schema::table('exam_pretest', function (Blueprint $table) {
            $table->tinyInteger('is_medical_staff')
                ->nullable()
                ->after('video_id')
                ->comment('是否医护人员：1-是，0-否，NULL-未采集');
        });
    }

    public function down(): void
    {
        Schema::table('exam_record', function (Blueprint $table) {
            $table->dropColumn('is_medical_staff');
        });

        Schema::table('exam_pretest', function (Blueprint $table) {
            $table->dropColumn('is_medical_staff');
        });
    }
};
