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
        Schema::create('topic', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 64)->comment('课程名称');
            $table->string('cover_url', 256)->comment('课程封面图URL');
            $table->string('intro', 500)->comment('课程简介');
            $table->integer('sort_order')->default(0)->comment('排序号');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用 1启用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic');
    }
};
