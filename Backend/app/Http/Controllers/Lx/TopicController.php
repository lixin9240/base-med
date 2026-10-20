<?php

namespace App\Http\Controllers\Lx;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lx\TopicCreateRequest;
use App\Http\Requests\Lx\TopicDeleteRequest;
use App\Http\Requests\Lx\TopicListRequest;
use App\Http\Requests\Lx\TopicUpdateRequest;
use App\Services\Lx\TopicService;
use Illuminate\Http\JsonResponse;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService
    ) {
    }

    public function list(TopicListRequest $request): JsonResponse
    {
        return $this->successPaginate($this->topicService->list($request->validated()));
    }

    public function create(TopicCreateRequest $request): JsonResponse
    {
        $id = $this->topicService->create($request->validated());

        return $this->success(['id' => $id]);
    }

    public function update(TopicUpdateRequest $request): JsonResponse
    {
        $id = $this->topicService->update($request->validated());

        return $this->success(['id' => $id]);
    }

    public function delete(TopicDeleteRequest $request): JsonResponse
    {
        $this->topicService->delete((int) $request->validated('id'));

        return $this->success(['success' => true]);
    }
}