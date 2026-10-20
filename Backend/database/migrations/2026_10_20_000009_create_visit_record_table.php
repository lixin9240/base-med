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
        Schema::create('visit_record', function (Blueprint $table) {
            $table->id();
            $table->string('dr_id', 32)->unique()->comment('匿名编号，顺序签发如DR-2026-000017');
            $table->unsignedBigInteger('share_link_id')->comment('关联访问链接ID（video_share_link）');
            $table->dateTime('entered_at')->comment('进入时间');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('share_link_id')->references('id')->on('video_share_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_record');
    }
};
