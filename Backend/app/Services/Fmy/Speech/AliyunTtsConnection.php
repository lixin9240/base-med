<?php

namespace App\Services\Fmy\Speech;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 调用阿里云智能语音交互短文本合成，返回 MP3 二进制。
 */
class AliyunTtsConnection
{
    public function __construct(
        private readonly string $appKey,
        private readonly AliyunNlsTokenClient $tokenClient,
        private readonly string $voice = 'xiaoyun',
        private readonly string $gateway = 'https://nls-gateway-cn-shanghai.aliyuncs.com/stream/v1/tts',
        private readonly int $sampleRate = 16000
    ) {
    }

    public function speak(string $text): string
    {
        $token = $this->tokenClient->token();
        $response = Http::timeout(30)
            ->withHeaders(['X-NLS-Token' => $token])
            ->asJson()
            ->post($this->gateway, [
                'appkey' => $this->appKey,
                'text' => $text,
                'format' => 'mp3',
                'sample_rate' => $this->sampleRate,
                'voice' => $this->voice,
            ]);

        $contentType = strtolower((string) $response->header('Content-Type'));
        $body = $response->body();
        if ($response->successful() && str_contains($contentType, 'audio')) {
            if ($body === '') {
                throw new RuntimeException('阿里云语音没有返回音频');
            }

            return $body;
        }

        throw new RuntimeException($this->failureText($body));
    }

    private function failureText(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            $message = (string) ($decoded['message'] ?? $decoded['Message'] ?? '');
            if ($message !== '') {
                return '阿里云语音合成失败：'.mb_substr($message, 0, 180);
            }
        }
        $plain = trim(preg_replace('/\s+/', ' ', $payload) ?? '');
        if ($plain === '') {
            return '阿里云语音合成失败';
        }

        return '阿里云语音合成失败：'.mb_substr($plain, 0, 180);
    }
}
