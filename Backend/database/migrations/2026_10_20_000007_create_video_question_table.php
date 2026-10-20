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
        Schema::create('video_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('video_id')->comment('关联内容ID');
            $table->string('stage', 16)->comment('环节：pretest前测 posttest后测');
            $table->unsignedBigInteger('question_id')->comment('关联题目ID');
            $table->integer('sort_order')->default(1)->comment('题目顺序（1-3）');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('video_id')->references('id')->on('video');
            $table->foreign('question_id')->references('id')->on('question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_question');
    }
};
