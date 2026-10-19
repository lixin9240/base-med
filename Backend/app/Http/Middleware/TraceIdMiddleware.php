<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TraceIdMiddleware
{
    /**
     * 为每个请求生成唯一 TraceId，用于链路追踪
     */
    public function handle($request, Closure $next)
    {
        $traceId = (string) Str::uuid();

        /**
         * 存入当前 Request 对象
         */
        $request->attributes->set(
            'trace_id',
            $traceId
        );

        /**
         * 写入日志上下文
         */
        Log::withContext([
            'trace_id' => $traceId,
        ]);

        return $next($request);
    }
}
