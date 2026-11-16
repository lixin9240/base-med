<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use App\Support\Result;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        BusinessException::class,
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 渲染异常为统一接口响应
     */
    public function render(
        $request,
        Throwable $e
    ) {
        /**
         * 参数验证异常
         */
        if ($e instanceof ValidationException) {
            return Result::error(
                ResponseCode::PARAM_ERROR,
                collect($e->errors())
                    ->flatten()
                    ->first()
            );
        }

        /**
         * 未登录
         */
        if ($e instanceof AuthenticationException) {
            return Result::error(
                ResponseCode::UNAUTHORIZED
            );
        }

        /**
         * 模型不存在
         */
        if ($e instanceof ModelNotFoundException) {
            return Result::error(
                ResponseCode::DATA_NOT_FOUND
            );
        }

        /**
         * 路由不存在
         */
        if ($e instanceof NotFoundHttpException) {
            return Result::error(
                ResponseCode::DATA_NOT_FOUND,
                '接口不存在'
            );
        }

        /**
         * 请求超过 PHP post_max_size（如大文件上传）
         */
        if ($e instanceof PostTooLargeException) {
            return Result::error(
                ResponseCode::FILE_TOO_LARGE,
                '上传内容过大，请缩小分片后重试'
            );
        }

        /**
         * 请求限流（throttle 中间件）
         */
        if ($e instanceof ThrottleRequestsException) {
            return Result::error(
                ResponseCode::COMMON_TOO_MANY_REQUESTS,
                '请求过于频繁，请稍后重试'
            );
        }

        /**
         * 业务异常
         */
        if ($e instanceof BusinessException) {
            return Result::error(
                $e->codeEnum,
                $e->getMessage()
            );
        }

        /**
         * 数据库异常
         */
        if ($e instanceof QueryException) {
            Log::channel('exception')->error(
                '数据库异常',
                [
                    'trace_id' => $request->attributes->get('trace_id'),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'message' => $e->getMessage(),
                ]
            );

            return Result::error(
                ResponseCode::DATABASE_ERROR,
                null,
                null,
                500
            );
        }

        /**
         * 系统异常日志
         */
        Log::channel('exception')->error(
            $e->getMessage(),
            [
                'trace_id' => $request->attributes->get('trace_id'),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        /**
         * 未知异常
         */
        return Result::error(
            ResponseCode::SYSTEM_ERROR,
            null,
            null,
            500
        );
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
