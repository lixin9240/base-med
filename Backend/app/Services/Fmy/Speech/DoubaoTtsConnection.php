<?php

namespace App\Services\Fmy\Speech;

use RuntimeException;

/**
 * 连接豆包双向流式语音合成，把一整句文本收成 MP3。
 */
class DoubaoTtsConnection
{
    private $socket;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $resourceId,
        private readonly string $speaker,
        private readonly string $host = 'openspeech.bytedance.com'
    ) {
    }

    public function speak(string $text): string
    {
        $this->connect();
        try {
            $this->openSession($text);

            return $this->collectAudio();
        } finally {
            $this->close();
        }
    }

    private function openSession(string $text): void
    {
        $this->sendEvent(1, '{}');
        $this->waitEvent(50);
        $sessionId = bin2hex(random_bytes(8));
        $this->sendEvent(100, $this->sessionJson($text), $sessionId);
        $this->waitEvent(150);
        $this->sendEvent(200, $this->taskJson($text), $sessionId);
        $this->sendEvent(102, '{}', $sessionId);
    }

    private function collectAudio(): string
    {
        $audio = '';
        while (true) {
            $message = $this->nextMessage();
            if ($message['type'] === 0x0f || in_array($message['event'], [51, 153], true)) {
                throw new RuntimeException($this->failureText($message['payload']));
            }
            if ($message['event'] === 352) {
                $audio .= $message['payload'];
            }
            if ($message['event'] === 152) {
                $this->assertSessionOk($message['payload']);
                break;
            }
        }
        if ($audio === '') {
            throw new RuntimeException('语音合成没有返回音频');
        }

        return $audio;
    }

    private function sessionJson(string $text): string
    {
        return json_encode([
            'user' => ['uid' => 'base-med'],
            'event' => 100,
            'namespace' => 'BidirectionalTTS',
            'req_params' => [
                'text' => $text,
                'speaker' => $this->speaker,
                'audio_params' => [
                    'format' => 'mp3',
                    'sample_rate' => 24000,
                    'bit_rate' => 64000,
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function taskJson(string $text): string
    {
        return json_encode([
            'event' => 200,
            'namespace' => 'BidirectionalTTS',
            'req_params' => ['text' => $text],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function connect(): void
    {
        $context = stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $socket = stream_socket_client(
            'ssl://'.$this->host.':443',
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if ($socket === false) {
            throw new RuntimeException('连接语音服务失败');
        }
        stream_set_timeout($socket, 20);
        $this->socket = $socket;
        $this->handshake();
    }

    private function handshake(): void
    {
        $key = base64_encode(random_bytes(16));
        $connectId = sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
        $request = "GET /api/v3/tts/bidirection HTTP/1.1\r\n"
            ."Host: {$this->host}\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Version: 13\r\n"
            ."X-Api-Key: {$this->apiKey}\r\n"
            ."X-Api-Resource-Id: {$this->resourceId}\r\n"
            ."X-Api-Connect-Id: {$connectId}\r\n\r\n";
        $this->writeAll($request);
        $response = $this->readHttpHead();
        if (! str_contains($response, '101')) {
            throw new RuntimeException('语音服务拒绝连接');
        }
    }

    private function sendEvent(int $event, string $json, string $sessionId = ''): void
    {
        $frame = DoubaoTtsProtocol::clientEvent($event, $json, $sessionId);
        $this->sendWs($frame);
    }

    private function waitEvent(int $expected): void
    {
        $message = $this->nextMessage();
        if ($message['event'] !== $expected) {
            throw new RuntimeException($this->failureText($message['payload']));
        }
    }

    /**
     * @return array{event: ?int, type: int, session_id: string, payload: string}
     */
    private function nextMessage(): array
    {
        return DoubaoTtsProtocol::decode($this->readWsMessage());
    }

    private function sendWs(string $payload, int $opcode = 0x2): void
    {
        $length = strlen($payload);
        $header = chr(0x80 | $opcode);
        if ($length < 126) {
            $header .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $header .= chr(0x80 | 126).pack('n', $length);
        } else {
            $header .= chr(0x80 | 127).pack('J', $length);
        }
        $mask = random_bytes(4);
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $mask[$i % 4];
        }
        $this->writeAll($header.$mask.$masked);
    }

    private function readWsMessage(): string
    {
        $payload = '';
        while (true) {
            $piece = $this->readWsFrame();
            if ($piece['opcode'] === 0x8) {
                throw new RuntimeException('语音连接已关闭');
            }
            if ($piece['opcode'] === 0x9) {
                $this->sendWs($piece['data'], 0xA);
                continue;
            }
            if ($piece['opcode'] === 0xA) {
                continue;
            }
            $payload .= $piece['data'];
            if ($piece['fin']) {
                return $payload;
            }
        }
    }

    /**
     * @return array{fin: bool, opcode: int, data: string}
     */
    private function readWsFrame(): array
    {
        $header = $this->readExact(2);
        $first = ord($header[0]);
        $second = ord($header[1]);
        $length = $second & 0x7f;
        if ($length === 126) {
            $length = unpack('n', $this->readExact(2))[1];
        } elseif ($length === 127) {
            $length = unpack('J', $this->readExact(8))[1];
        }
        $mask = ($second & 0x80) !== 0 ? $this->readExact(4) : '';
        $data = $length > 0 ? $this->readExact($length) : '';
        if ($mask !== '') {
            $data = $this->unmask($data, $mask);
        }

        return [
            'fin' => ($first & 0x80) !== 0,
            'opcode' => $first & 0x0f,
            'data' => $data,
        ];
    }

    private function unmask(string $data, string $mask): string
    {
        $plain = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $plain .= $data[$i] ^ $mask[$i % 4];
        }

        return $plain;
    }

    private function readHttpHead(): string
    {
        $head = '';
        while (! str_contains($head, "\r\n\r\n")) {
            $chunk = fread($this->socket, 1);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('语音服务没有完成握手');
            }
            $head .= $chunk;
            if (strlen($head) > 8192) {
                throw new RuntimeException('语音服务握手异常');
            }
        }

        return $head;
    }

    private function readExact(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('语音连接中断');
            }
            $data .= $chunk;
        }

        return $data;
    }

    private function writeAll(string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($this->socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('语音连接发送失败');
            }
            $offset += $written;
        }
    }

    private function assertSessionOk(string $payload): void
    {
        $meta = json_decode($payload, true);
        $code = is_array($meta) ? (int) ($meta['status_code'] ?? 20000000) : 20000000;
        if ($code !== 20000000) {
            throw new RuntimeException($this->failureText($payload));
        }
    }

    private function failureText(string $payload): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $payload) ?? '');
        if ($text === '') {
            return '语音合成失败';
        }

        return '语音合成失败：'.mb_substr($text, 0, 180);
    }

    private function close(): void
    {
        if (! is_resource($this->socket)) {
            return;
        }
        try {
            $this->sendEvent(2, '{}');
        } catch (RuntimeException) {
            // 连接已经结束时不再向外抛出关闭错误
        }
        fclose($this->socket);
        $this->socket = null;
    }
}
