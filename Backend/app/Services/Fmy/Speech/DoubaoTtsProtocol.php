<?php

namespace App\Services\Fmy\Speech;

use RuntimeException;

/**
 * 豆包双向流式语音合成的二进制帧。整数均为大端。
 */
class DoubaoTtsProtocol
{
    public static function clientEvent(int $event, string $json, string $sessionId = ''): string
    {
        $body = pack('N', $event);
        if ($sessionId !== '') {
            $body .= self::packString($sessionId);
        }
        $body .= self::packString($json);

        return "\x11\x14\x10\x00".$body;
    }

    /**
     * @return array{event: ?int, type: int, session_id: string, payload: string}
     */
    public static function decode(string $frame): array
    {
        if (strlen($frame) < 4) {
            throw new RuntimeException('语音帧长度不足');
        }

        $offset = (ord($frame[0]) & 0x0f) * 4;
        $type = (ord($frame[1]) >> 4) & 0x0f;
        $flags = ord($frame[1]) & 0x0f;
        $compression = ord($frame[2]) & 0x0f;

        if ($type === 0x0f) {
            return self::decodeError($frame);
        }

        $event = null;
        if (($flags & 0x04) === 0x04) {
            $event = self::i32($frame, $offset);
            $offset += 4;
        }

        $sessionId = '';
        if (self::hasSessionId($event)) {
            [$sessionId, $offset] = self::readString($frame, $offset);
        }
        if (self::hasConnectionId($event)) {
            [, $offset] = self::readString($frame, $offset);
        }

        [$payload] = self::readString($frame, $offset);
        if ($compression === 1) {
            $decoded = gzdecode($payload);
            $payload = $decoded === false ? $payload : $decoded;
        }

        return [
            'event' => $event,
            'type' => $type,
            'session_id' => $sessionId,
            'payload' => $payload,
        ];
    }

    /**
     * @return array{event: ?int, type: int, session_id: string, payload: string}
     */
    private static function decodeError(string $frame): array
    {
        $code = self::i32($frame, 4);
        [$payload] = self::readString($frame, 8);

        return [
            'event' => null,
            'type' => 0x0f,
            'session_id' => '',
            'payload' => 'code='.$code.' '.$payload,
        ];
    }

    private static function hasSessionId(?int $event): bool
    {
        return $event !== null && $event >= 100;
    }

    private static function hasConnectionId(?int $event): bool
    {
        return in_array($event, [50, 51, 52], true);
    }

    private static function packString(string $value): string
    {
        return pack('N', strlen($value)).$value;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function readString(string $frame, int $offset): array
    {
        $length = self::u32($frame, $offset);
        $offset += 4;
        if ($length < 0 || $offset + $length > strlen($frame)) {
            throw new RuntimeException('语音帧内容不完整');
        }

        return [substr($frame, $offset, $length), $offset + $length];
    }

    private static function u32(string $frame, int $offset): int
    {
        self::need($frame, $offset, 4);

        return unpack('N', substr($frame, $offset, 4))[1];
    }

    private static function i32(string $frame, int $offset): int
    {
        $value = self::u32($frame, $offset);
        if ($value >= 0x80000000) {
            return $value - 0x100000000;
        }

        return $value;
    }

    private static function need(string $frame, int $offset, int $length): void
    {
        if ($offset < 0 || $offset + $length > strlen($frame)) {
            throw new RuntimeException('语音帧内容不完整');
        }
    }
}
