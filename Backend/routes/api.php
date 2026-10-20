<?php


use App\Http\Controllers\Lx\ExamRecordController;
use App\Http\Controllers\Lx\QuestionController;
use App\Http\Controllers\Lx\ShareLinkController;

use App\Http\Controllers\Wjc\Admin\AuthController;
use App\Http\Controllers\Wjc\Admin\CategoryController;
use App\Http\Controllers\Wjc\Admin\VideoQuestionController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LX-答题记录模块
|--------------------------------------------------------------------------
*/

Route::prefix('admin/exam-record')->middleware('auth:admin')->group(function () {
    Route::get(uri: '/list', action: [ExamRecordController::class, 'list']);
    Route::get(uri: '/detail', action: [ExamRecordController::class, 'detail']);
    Route::get(uri: '/export', action: [ExamRecordController::class, 'export']);
});

/*
|--------------------------------------------------------------------------
| LX-题库管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/question')->middleware('auth:admin')->group(function () {
    Route::get(uri: '/list', action: [QuestionController::class, 'list']);
    Route::post(uri: '/update', action: [QuestionController::class, 'update']);
    Route::post(uri: '/delete', action: [QuestionController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| LX-访问链接管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/share-link')->middleware('auth:admin')->group(function () {
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

/*
|--------------------------------------------------------------------------
| WJC-题目配置模块（2.3）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/video-question')->middleware('auth:admin')->group(function () {
    // 2.3.1 获取前/后测题目列表
    Route::get(uri: '/list', action: [VideoQuestionController::class, 'list']);

    // 2.3.2 新增题目
    Route::post(uri: '/add-question', action: [VideoQuestionController::class, 'addQuestion']);

    // 2.3.3 编辑题目
    Route::post(uri: '/update-question', action: [VideoQuestionController::class, 'updateQuestion']);

    // 2.3.4 下载模板
    Route::get(uri: '/download-template', action: [VideoQuestionController::class, 'downloadTemplate']);

    // 2.3.6 导出本组题目
    Route::get(uri: '/export', action: [VideoQuestionController::class, 'export']);

    // 2.3.7 移除本组题目
    Route::post(uri: '/remove', action: [VideoQuestionController::class, 'remove']);
});

/*
|--------------------------------------------------------------------------
| WJC-题目导入（2.3.5，独立前缀 video）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/video')->middleware('auth:admin')->group(function () {
    // 2.3.5 导入本组题目（创建导入任务）
    Route::post(uri: '/import-question', action: [VideoQuestionController::class, 'importQuestion']);
});

/*
|--------------------------------------------------------------------------
| WJC-分类管理模块（2.7）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/category')->middleware('auth:admin')->group(function () {
    // 2.7.1 分类列表
    Route::get(uri: '/list', action: [CategoryController::class, 'list']);

    // 2.7.2 新增分类
    Route::post(uri: '/create', action: [CategoryController::class, 'create']);

    // 2.7.3 编辑分类
    Route::post(uri: '/update', action: [CategoryController::class, 'update']);

    // 2.7.4 删除分类
    Route::post(uri: '/delete', action: [CategoryController::class, 'delete']);
});
