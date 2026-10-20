<?php

namespace App\Http\Controllers;

use App\Enums\ResponseCode;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * 成功响应
     *
     * @param mixed        $data 业务数据（接口文档「返回示例」的 data 部分）
     * @param ResponseCode $code 响应码，默认 SUCCESS
     * @param string|null  $msg  提示信息，为空时使用响应码默认提示
     */
    protected function success(
        mixed $data = null,
        ResponseCode $code = ResponseCode::SUCCESS,
        ?string $msg = null
    ): JsonResponse {
        return Result::success($msg ?? $code->msg(), $data);
    }

    /**
     * 失败响应
     *
     * @param ResponseCode $code 错误码枚举
     * @param string|null  $msg  提示信息，为空时使用错误码默认提示
     * @param mixed        $data 附加数据
     */
    protected function fail(
        ResponseCode $code,
        ?string $msg = null,
        mixed $data = null
    ): JsonResponse {
        return Result::error($code, $msg, $data);
    }

    /**
     * 分页成功响应（统一分页 JSON 模板）
     *
     * 输出：{ list, total, page, page_size }（与接口文档「分页约定」一致）
     */
    protected function successPaginate(
        LengthAwarePaginator $paginator,
        ResponseCode $code = ResponseCode::SUCCESS,
        ?string $msg = null
    ): JsonResponse {
        return $this->success([
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->perPage(),
        ], $code, $msg);
    }
}
