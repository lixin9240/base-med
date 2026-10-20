<?php

namespace App\Http\Controllers\Lx;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lx\ShareLinkCreateRequest;
use App\Http\Requests\Lx\ShareLinkDeleteRequest;
use App\Http\Requests\Lx\ShareLinkListRequest;
use App\Http\Requests\Lx\ShareLinkUpdateStatusRequest;
use App\Http\Requests\Lx\ShareLinkVisitRecordListRequest;
use App\Services\Lx\ShareLinkService;
use Illuminate\Http\JsonResponse;

class ShareLinkController extends Controller
{
    public function __construct(
        private readonly ShareLinkService $shareLinkService
    ) {
    }

    public function create(ShareLinkCreateRequest $request): JsonResponse
    {
        $data = $this->shareLinkService->create($request->validated());

        return $this->success($data);
    }

    public function list(ShareLinkListRequest $request): JsonResponse
    {
        return $this->successPaginate($this->shareLinkService->list($request->validated()));
    }

    public function updateStatus(ShareLinkUpdateStatusRequest $request): JsonResponse
    {
        $this->shareLinkService->updateStatus($request->validated());

        return $this->success(['success' => true]);
    }

    public function delete(ShareLinkDeleteRequest $request): JsonResponse
    {
        $this->shareLinkService->delete((int) $request->validated('id'));

        return $this->success(['success' => true]);
    }

    public function visitRecordList(ShareLinkVisitRecordListRequest $request): JsonResponse
    {
        return $this->successPaginate($this->shareLinkService->visitRecordList($request->validated()));
    }
}