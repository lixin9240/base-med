<?php

namespace App\Http\Controllers\Lx;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lx\QuestionDeleteRequest;
use App\Http\Requests\Lx\QuestionListRequest;
use App\Http\Requests\Lx\QuestionUpdateRequest;
use App\Services\Lx\QuestionService;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionService $questionService
    ) {
    }

    public function list(QuestionListRequest $request): JsonResponse
    {
        return $this->successPaginate($this->questionService->list($request->validated()));
    }

    public function update(QuestionUpdateRequest $request): JsonResponse
    {
        $id = $this->questionService->update($request->validated());

        return $this->success(['id' => $id]);
    }

    public function delete(QuestionDeleteRequest $request): JsonResponse
    {
        $this->questionService->delete((int) $request->validated('id'));

        return $this->success(['success' => true]);
    }
}