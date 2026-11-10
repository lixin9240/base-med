<?php

namespace App\Services\Fmy\Speech;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 用 AccessKey 向阿里云换取短期 NLS Token，并缓存到过期前。
 */
class AliyunNlsTokenClient
{
    public function __construct(
        private readonly string $accessKeyId,
        private readonly string $accessKeySecret,
        private readonly string $metaHost = 'nls-meta.cn-shanghai.aliyuncs.com'
    ) {
    }

    public function token(): string
    {
        $cached = Cache::get($this->cacheKey());
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $payload = $this->requestToken();
        $token = (string) ($payload['Token']['Id'] ?? '');
        $expire = (int) ($payload['Token']['ExpireTime'] ?? 0);
        if ($token === '' || $expire <= time()) {
            throw new RuntimeException('阿里云语音 Token 无效');
        }

        $ttl = max(60, $expire - time() - 120);
        Cache::put($this->cacheKey(), $token, $ttl);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestToken(): array
    {
        $params = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'CreateToken',
            'Format' => 'JSON',
            'RegionId' => 'cn-shanghai',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2019-02-28',
        ];
        ksort($params);
        $canonical = [];
        foreach ($params as $key => $value) {
            $canonical[] = $this->percentEncode($key).'='.$this->percentEncode((string) $value);
        }
        $query = implode('&', $canonical);
        $stringToSign = 'GET&'.$this->percentEncode('/').'&'.$this->percentEncode($query);
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret.'&', true));
        $url = 'https://'.$this->metaHost.'/?'.$query.'&Signature='.$this->percentEncode($signature);

        $response = Http::timeout(15)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('获取阿里云语音 Token 失败');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('阿里云语音 Token 响应异常');
        }

        return $json;
    }

    private function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
    }

    private function cacheKey(): string
    {
        return 'speech:aliyun_nls_token:'.hash('sha256', $this->accessKeyId);
    }
}
