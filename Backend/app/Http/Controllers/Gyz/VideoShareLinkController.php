<?php

namespace App\Http\Controllers\Gyz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gyz\ShareLinkDetailRequest;
use App\Http\Requests\Gyz\ShareLinkGenerateRequest;
use App\Http\Requests\Gyz\ShareLinkRevokeRequest;
use App\Services\Gyz\VideoShareLinkService;
use App\Support\Result;
use Illuminate\Http\JsonResponse;

/**
 * 视频分享链接控制器
 *
 * 只做请求接收与结果返回，业务逻辑全部在 VideoShareLinkService。
 */
class VideoShareLinkController extends Controller
{
    public function __construct(
        private readonly VideoShareLinkService $shareLinkService
    ) {
    }

    /**
     * 生成 / 重新生成视频分享链接
     */
    public function generate(ShareLinkGenerateRequest $request): JsonResponse
    {
        $data = $this->shareLinkService->generate($request->validated());

        return Result::success('链接生成成功', $data);
    }

    /**
     * 获取当前生效链接详情
     */
    public function detail(ShareLinkDetailRequest $request): JsonResponse
    {
        $data = $this->shareLinkService->detail($request->validated());

        return Result::success('获取成功', $data);
    }

    /**
     * 撤回生效链接
     */
    public function revoke(ShareLinkRevokeRequest $request): JsonResponse
    {
        $this->shareLinkService->revoke($request->validated());

        return Result::success('撤回成功', ['success' => true]);
    }
}