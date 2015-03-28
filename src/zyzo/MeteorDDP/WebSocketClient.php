<?php
namespace zyzo\MeteorDDP;

class WebSocketClient {

    const OPCODE_MASK = 15; // 0x00001111
    const PAYLOAD_LEN_MASK = 127; // 0x01111111
    const FIN_FRAME = 128;
    const TEXT_FRAME = 1;
    const BINARY_FRAME = 2;
    const CLOSE_FRAME = 8;
    const PING_FRAME = 9;
    const PONG_FRAME = 10;

    // source : http://stackoverflow.com/questions/11016164/how-to-send-websocket-hybi-17-frame-with-php-server
    public static function draft10Encode($payload, $type = 'text', $masked = true, $maskKey = null)
    {
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);

        switch($type)
        {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::TEXT_FRAME;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::CLOSE_FRAME;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::PING_FRAME ;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::PONG_FRAME;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if($payloadLength > 65535)
        {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for($i = 0; $i < 8; $i++)
            {
                $frameHead[$i+2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if($frameHead[2] > 127)
            {
                return false;
            }
        }
        elseif($payloadLength > 125)
        {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        }
        else
        {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }
        // convert frame-head to string:
        foreach(array_keys($frameHead) as $i)
        {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if($masked === true)
        {
            $mask = array();
            if ($maskKey === null) {
                // generate a random mask:
                for ($i = 0; $i < 4; $i++) {
                    $mask[$i] = chr(rand(0, 255));
                }
            } else {
                $mask = $maskKey; // useful for debug only
            }
            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        for($i = 0; $i < $payloadLength; $i++)
        {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }
        return $frame;
    }

    public static function draft10Decode($frame) {
        $frameNum = 0;
        $frameLen = strlen($frame);
        $ptr = 0;
        $result = array();
        while ($ptr < $frameLen) {
            switch (ord($frame[0 + $ptr]) & WebSocketClient::OPCODE_MASK) {
                case WebSocketClient::TEXT_FRAME :
                    break;
                case WebSocketClient::PING_FRAME :
                    break;
            }
            $payloadLen = ord($frame[1 + $ptr]) & WebSocketClient::PAYLOAD_LEN_MASK;
            $result[$frameNum] = substr($frame, 2 + $ptr, $payloadLen);
            $ptr += $payloadLen + 2;
            $frameNum += 1;
        }
        return $result;
    }

    public static function handshakeMessage($host) {
        return "GET /websocket HTTP/1.1" . "\r\n" .
        "Connection: Upgrade" . "\r\n" .
        "Upgrade: websocket" . "\r\n" .
        "Origin: http://localhost" . "\r\n" .
        "Host: $host" . "\r\n" .
        "Sec-WebSocket-Version: 13\r\n" .
        "Sec-WebSocket-Key: Hxu4fCuzO7VK81vf0oNu4Q==\r\n" .
        "Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits\r\n" .
        "Content-Length: 0" . "\r\n" . "\r\n";
    }
}