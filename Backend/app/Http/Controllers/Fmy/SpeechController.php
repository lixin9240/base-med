<?php

namespace App\Http\Controllers\Fmy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fmy\SpeechSynthesizeRequest;
use App\Services\Fmy\Speech\SpeechSynthesizeService;
use Illuminate\Http\JsonResponse;

class SpeechController extends Controller
{
    public function __construct(
        private readonly SpeechSynthesizeService $speechSynthesizeService
    ) {
    }

    /**
     * 将一句预设提示词合成为 MP3。
     */
    public function synthesize(SpeechSynthesizeRequest $request): JsonResponse
    {
        $data = $this->speechSynthesizeService->synthesize(
            (string) $request->validated('text')
        );

        return $this->success($data);
    }
}
