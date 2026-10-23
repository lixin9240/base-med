<?php


use App\Http\Controllers\Fmy\DrController;
use App\Http\Controllers\Fmy\ExamController;
use App\Http\Controllers\Fmy\VideoController;
use App\Http\Controllers\Lx\ExamRecordController;
use App\Http\Controllers\Lx\QuestionController;
use App\Http\Controllers\Lx\TopicController;

use App\Http\Controllers\Wjc\Admin\AdminUserController;
use App\Http\Controllers\Wjc\Admin\AuthController;
use App\Http\Controllers\Wjc\Admin\CategoryController;
use App\Http\Controllers\Wjc\Admin\VideoQuestionController;

use App\Http\Controllers\Gyz\ImportTaskController;
use App\Http\Controllers\Gyz\VideoController as GyzVideoController;
use App\Http\Controllers\Gyz\OperationLogController;
use App\Http\Controllers\Gyz\StatisticsController;
use App\Http\Controllers\Gyz\VideoShareLinkController;

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
| LX-课程管理模块
|--------------------------------------------------------------------------
*/
Route::prefix('admin/topic')->middleware('auth:admin')->group(function () {
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
Route::prefix('admin/question')->middleware('auth:admin')->group(function () {
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
| WJC-管理员用户管理模块
|--------------------------------------------------------------------------
*/

Route::prefix('admin/admin-user')->middleware('auth:admin')->group(function () {
    Route::get(uri: '/list', action: [AdminUserController::class, 'list']);
    Route::post(uri: '/create', action: [AdminUserController::class, 'create']);
    Route::post(uri: '/update', action: [AdminUserController::class, 'update']);
    Route::post(uri: '/delete', action: [AdminUserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| 用户管理-启用/禁用（2.2.5，列表页「禁用登录 / 恢复登录」按钮）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/user')->middleware('auth:admin')->group(function () {
    // 按状态筛选用户列表（2.2.6，status 必填：0禁用 1正常）
    Route::get(uri: '/list', action: [AdminUserController::class, 'listByStatus']);

    // 启用/禁用用户（2.2.5，禁用后该用户立即无法登录，已签发 token 同步失效）
    Route::post(uri: '/update-status', action: [AdminUserController::class, 'updateStatus']);
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
    // 3.1 获取匿名 DR 编号（点击视频进入时生成访问记录，免登录）
    Route::get(uri: '/dr/create', action: [DrController::class, 'create']);

    // 3.2 用户端首页视频列表（免登录、无需 dr_id）
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

    // 3.9 再次答题（重答后测，复用最近一次前测结果提高正确率）
    Route::post(uri: '/exam/retake', action: [ExamController::class, 'retake']);

    // 3.8 获取答题结果
    Route::get(uri: '/exam/result', action: [ExamController::class, 'result']);
});

/*
|--------------------------------------------------------------------------
| GYZ-视频管理模块（2.2）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/video')->middleware('auth:admin')->group(function () {
    // 2.2.1 视频列表
    Route::get(uri: '/list', action: [GyzVideoController::class, 'list']);

    // 2.2.2 视频详情（编辑弹窗回填）
    Route::get(uri: '/detail', action: [GyzVideoController::class, 'detail']);

    // 2.2.3 新增视频（弹窗保存）
    Route::post(uri: '/create', action: [GyzVideoController::class, 'create']);

    // 2.2.4 编辑视频（弹窗保存）
    Route::post(uri: '/update', action: [GyzVideoController::class, 'update']);

    // 2.2.5 上架/下架切换（列表页开关）
    Route::post(uri: '/update-status', action: [GyzVideoController::class, 'updateStatus']);

    // 2.2.6 删除视频（软删除）
    Route::post(uri: '/delete', action: [GyzVideoController::class, 'delete']);

    // 2.2.7 初始化视频上传（本地上传，source_type=2）
    Route::post(uri: '/upload/init', action: [GyzVideoController::class, 'uploadInit']);

    // 上传封面图片（本地上传，source_type=2）
    Route::post(uri: '/upload/cover', action: [GyzVideoController::class, 'uploadCover']);

    // 本地模拟辅助：上传单个分片（OSS 直传时由前端 SDK 分片，可跳过）
    Route::post(uri: '/upload/chunk', action: [GyzVideoController::class, 'uploadChunk']);

    // 2.2.8 完成视频上传
    Route::post(uri: '/upload/complete', action: [GyzVideoController::class, 'uploadComplete']);
});

/*
|--------------------------------------------------------------------------
| GYZ-数据统计模块（2.5）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/statistics')->middleware('auth:admin')->group(function () {
    // 2.5.1 统计大盘（4 张指标卡）
    Route::get(uri: '/overview', action: [StatisticsController::class, 'overview']);

    // 2.5.2 各视频答题情况
    Route::get(uri: '/video-list', action: [StatisticsController::class, 'videoList']);

    // 2.5.3 最容易答错的题
    Route::get(uri: '/wrong-question', action: [StatisticsController::class, 'wrongQuestion']);

    // 2.5.4 导出统计（Excel 文件流）
    Route::get(uri: '/export', action: [StatisticsController::class, 'export']);
});

/*
|--------------------------------------------------------------------------
| GYZ-批量导入任务模块（2.8）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/import')->middleware('auth:admin')->group(function () {
    // 2.8.1 任务列表（懒触发校验：校验中的任务就地完成校验）
    Route::get(uri: '/task-list', action: [ImportTaskController::class, 'taskList']);

    // 2.8.2 任务详情（校验结果与错误报告）
    Route::get(uri: '/task-detail', action: [ImportTaskController::class, 'taskDetail']);

    // 2.8.3 确认导入
    Route::post(uri: '/confirm', action: [ImportTaskController::class, 'confirm']);
});

/*
|--------------------------------------------------------------------------
| GYZ-操作日志模块（2.9）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/operation-log')->middleware('auth:admin')->group(function () {
    // 2.9.1 操作日志列表
    Route::get(uri: '/list', action: [OperationLogController::class, 'list']);
});

/*
|--------------------------------------------------------------------------
| GYZ-链接模块（公众号分享链接，2.12）
|--------------------------------------------------------------------------
*/

Route::prefix('admin/share-link')->middleware('auth:admin')->group(function () {
    // 2.12.1 生成 / 重新生成视频分享链接
    Route::post(uri: '/generate', action: [VideoShareLinkController::class, 'generate']);

    // 2.12.2 获取当前生效链接详情
    Route::get(uri: '/detail', action: [VideoShareLinkController::class, 'detail']);

    // 2.12.3 撤回生效链接
    Route::post(uri: '/revoke', action: [VideoShareLinkController::class, 'revoke']);
});
