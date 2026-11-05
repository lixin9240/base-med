<?php

namespace Tests\Unit;

use App\Services\Fmy\Speech\DoubaoTtsProtocol;
use PHPUnit\Framework\TestCase;

class DoubaoTtsProtocolTest extends TestCase
{
    public function test_client_event_uses_big_endian_header(): void
    {
        $frame = DoubaoTtsProtocol::clientEvent(1, '{}');

        $this->assertSame("\x11\x14\x10\x00", substr($frame, 0, 4));
        $this->assertSame(1, unpack('N', substr($frame, 4, 4))[1]);
        $this->assertSame('{}', substr($frame, 12));
    }

    public function test_decode_audio_response(): void
    {
        $sessionId = 'sid123456789';
        $audio = 'mp3-bytes';
        $frame = "\x11\xb4\x00\x00"
            .pack('N', 352)
            .pack('N', strlen($sessionId)).$sessionId
            .pack('N', strlen($audio)).$audio;

        $message = DoubaoTtsProtocol::decode($frame);

        $this->assertSame(352, $message['event']);
        $this->assertSame($sessionId, $message['session_id']);
        $this->assertSame($audio, $message['payload']);
    }
}
