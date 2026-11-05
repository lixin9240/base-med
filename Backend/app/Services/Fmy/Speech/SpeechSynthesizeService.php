<?php

namespace App\Services\Fmy\Speech;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * 把一句预设提示词合成为 MP3，并按文本缓存。
 */
class SpeechSynthesizeService
{
    public function synthesize(string $text): array
    {
        $line = trim($text);
        $cached = $this->cachedAudio($line);
        if ($cached !== null) {
            return $this->result($cached);
        }

        try {
            $audio = $this->client()->speak($line);
        } catch (RuntimeException $exception) {
            Log::warning('豆包语音合成失败', ['message' => $exception->getMessage()]);
            throw new BusinessException(ResponseCode::THIRD_PARTY_TIMEOUT, '语音合成失败，请稍后再试');
        }

        $this->storeAudio($line, $audio);

        return $this->result($audio);
    }

    private function client(): DoubaoTtsConnection
    {
        $key = (string) config('services.doubao_tts.key');
        if ($key === '') {
            throw new BusinessException(ResponseCode::SYSTEM_CONFIG_ERROR, '语音服务未配置');
        }

        return new DoubaoTtsConnection(
            $key,
            (string) config('services.doubao_tts.resource_id'),
            (string) config('services.doubao_tts.speaker'),
            (string) config('services.doubao_tts.host')
        );
    }

    private function cachedAudio(string $text): ?string
    {
        $path = $this->cachePath($text);
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $audio = Storage::disk('local')->get($path);

        return $audio === '' ? null : $audio;
    }

    private function storeAudio(string $text, string $audio): void
    {
        Storage::disk('local')->put($this->cachePath($text), $audio);
    }

    private function cachePath(string $text): string
    {
        $speaker = (string) config('services.doubao_tts.speaker');

        return 'speech/'.hash('sha256', $speaker."\n".$text).'.mp3';
    }

    private function result(string $audio): array
    {
        return [
            'format' => 'mp3',
            'audio_base64' => base64_encode($audio),
        ];
    }
}
