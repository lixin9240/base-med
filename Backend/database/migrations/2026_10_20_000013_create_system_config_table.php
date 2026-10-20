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
        Schema::create('system_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('config_key', 64)->unique()->comment('配置项');
            $table->string('config_value', 512)->comment('配置值');
            $table->string('remark', 128)->nullable()->comment('说明');
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('updated_by')->nullable()->comment('最后修改人ID');
            $table->dateTime('updated_at')->nullable();

            $table->foreign('updated_by')->references('id')->on('admin_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
