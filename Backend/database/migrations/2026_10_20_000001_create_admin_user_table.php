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
        Schema::create('admin_user', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique()->comment('登录账号');
            $table->string('password_hash', 128)->comment('密码哈希');
            $table->string('name', 32)->comment('姓名');
            $table->string('role', 16)->default('admin')->comment('角色：admin统一管理员');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用 1正常');
            $table->dateTime('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_user');
    }
};
