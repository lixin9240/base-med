<?php


use App\Http\Controllers\Fmy\DrController;
use App\Http\Controllers\Fmy\ExamController;
use App\Http\Controllers\Fmy\VideoController;
use App\Http\Controllers\Lx\ExamRecordController;
use App\Http\Controllers\Lx\QuestionController;
use App\Http\Controllers\Lx\TopicController;

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

/*
|--------------------------------------------------------------------------
| FMY-用户端接口（匿名免登录，仅传 dr_id）
|--------------------------------------------------------------------------
*/

Route::prefix('public')->group(function () {
    // 3.1 获取匿名 DR 编号（点击链接进入时生成访问记录）
    Route::get(uri: '/dr/create', action: [DrController::class, 'create']);

    // 3.2 用户端首页视频列表
    Route::get(uri: '/video/list', action: [VideoController::class, 'list']);

    // 3.3 视频详情（观看视频页）
    Route::get(uri: '/video/detail', action: [VideoController::class, 'detail']);

    // 3.4 上报视频观看进度（观看进度≥90%解锁后测）
    Route::post(uri: '/video/progress', action: [VideoController::class, 'progress']);

    // 3.6 获取视频题目列表（学前/看完答题页共用）
    Route::get(uri: '/video/question-list', action: [VideoController::class, 'questionList']);

    // 3.5 提交学前答题
    Route::post(uri: '/exam/submit-pre', action: [ExamController::class, 'submitPre']);

    // 3.7 提交看完答题（后测，需观看进度达到门槛）
    Route::post(uri: '/exam/submit-post', action: [ExamController::class, 'submitPost']);

    // 3.8 获取答题结果
    Route::get(uri: '/exam/result', action: [ExamController::class, 'result']);
});
