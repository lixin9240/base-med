<?php


use App\Http\Controllers\Lx\ExamRecordController;
use App\Http\Controllers\Lx\QuestionController;
use App\Http\Controllers\Lx\ShareLinkController;
use App\Http\Controllers\Lx\TopicController;

use App\Http\Controllers\Wjc\Admin\AuthController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LX-答题记录模块
|--------------------------------------------------------------------------
*/

Route::prefix('admin/exam-record')->group(function () {
    Route::get(uri: '/list', action: [ExamRecordController::class, 'list']);
    Route::get(uri: '/detail', action: [ExamRecordController::class, 'detail']);
    Route::get(uri: '/export', action: [ExamRecordController::class, 'export']);
});

/*
|--------------------------------------------------------------------------
| LX-课程管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/topic')->group(function () {
    Route::get(uri: '/list', action: [TopicController::class, 'list']);
    Route::post(uri: '/create', action: [TopicController::class, 'create']);
    Route::post(uri: '/update', action: [TopicController::class, 'update']);
    Route::post(uri: '/delete', action: [TopicController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| LX-题库管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/question')->group(function () {
    Route::get(uri: '/list', action: [QuestionController::class, 'list']);
    Route::post(uri: '/update', action: [QuestionController::class, 'update']);
    Route::post(uri: '/delete', action: [QuestionController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| LX-访问链接管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/share-link')->group(function () {
    Route::post(uri: '/create', action: [ShareLinkController::class, 'create']);
    Route::get(uri: '/list', action: [ShareLinkController::class, 'list']);
    Route::post(uri: '/update-status', action: [ShareLinkController::class, 'updateStatus']);
    Route::post(uri: '/delete', action: [ShareLinkController::class, 'delete']);
    Route::get(uri: '/visit-record-list', action: [ShareLinkController::class, 'visitRecordList']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| WJC-管理员认证模块（2.1）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/auth')->group(function () {
    // 2.1.1 管理员登录
    Route::post('login', [AuthController::class, 'login']);

    // 2.1.4 发送重置密码验证码（忘记密码）
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

    // 2.1.5 重置密码
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // 需要登录的接口
    Route::middleware('auth:admin')->group(function () {
        // 2.1.2 获取当前登录管理员信息
        Route::get('profile', [AuthController::class, 'profile']);

        // 2.1.3 管理员登出
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
