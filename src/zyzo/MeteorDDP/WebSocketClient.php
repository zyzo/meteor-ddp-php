<?php
namespace zyzo\MeteorDDP;
require_once __DIR__ . '/enum/FrameStatus.php';
use zyzo\MeteorDDP\enum\FrameStatus;

class WebSocketClient {

    const OPCODE_MASK = 15; // 0x00001111
    const RSV_MASK =  112; // 0x01110000
    const PAYLOAD_LEN_MASK = 127; // 0x01111111
    const FIN_FRAME = 128;
    const TEXT_FRAME_OPCODE = 1;
    const CLOSE_FRAME_OPCODE = 8;
    const PING_FRAME_OPCODE = 9;
    const PONG_FRAME_OPCODE = 10;
    const PAYLOAD_SIZE_16BIT_OPCODE = 126;
    const PAYLOAD_SIZE_64BIT_OPCODE = 127;
    /**
     * @var int
     */
    private $lastExceedBytes;

    public function __construct() {
        $this->lastExceedBytes = 0;
    }

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
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::TEXT_FRAME_OPCODE;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::CLOSE_FRAME_OPCODE;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::PING_FRAME_OPCODE ;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = WebSocketClient::FIN_FRAME | WebSocketClient::PONG_FRAME_OPCODE;
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


    /**
     * Decode a WebSocket frame
     * @param $frame
     * @return array
     *       return an object with format (type, payload)
     *       with `type`  : the opcode type
     *            `payload` : the actual data in string
     */
    public function draft10Decode($frame) {
        $this->lastExceeds();
        $result = new \stdClass();
        switch (ord($frame[0]) & WebSocketClient::OPCODE_MASK) {
            case WebSocketClient::TEXT_FRAME_OPCODE :
                $result->type = 'text';
                break;
            case WebSocketClient::PING_FRAME_OPCODE :
                $result->type = 'ping';
                break;
            case WebSocketClient::PONG_FRAME_OPCODE :
                $result->type = 'pong';
                break;
            case WebSocketClient::CLOSE_FRAME_OPCODE :
                $result->type = 'close';
                break;
            default:
            $result->type = 'unknown';
        }

        $payloadLen = ord($frame[1]) & WebSocketClient::PAYLOAD_LEN_MASK;
        $skip = 2;
        if ($payloadLen == self::PAYLOAD_SIZE_16BIT_OPCODE)
            $skip += 2;
        if ($payloadLen == self::PAYLOAD_SIZE_64BIT_OPCODE)
            $skip += 8;

        $result->payload = substr($frame, $skip);
        $this->lastExceeds();
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

    public function lastExceeds() {
        return $this->lastExceedBytes;
    }

    public function handshakeEnd($text) {
        $http_delimeter = "\r\n";
        $end_delimeter = "{$http_delimeter}{$http_delimeter}";
        $pos = strpos($text, $end_delimeter);
        if ($pos === false)
            return false;
        return $pos + strlen($end_delimeter);
    }

    /**
     * Helper function to detect if the argument is a valid WebSocket frame.
     * This function is "best effort", because it relies on some characteristics of WS framing and message
     * returned by DDP server.
     * @param $text string
     *         The frame to validate
     * @return FrameStatus
     *         valid status of the argument
     */
    public function validFrame($text) {

        // reserved bits must be 0, DDP defines no extension
        if ((ord($text[0]) & self::RSV_MASK) !== 0) {
            return new FrameStatus(FrameStatus::NOT_VALID);
        }

        // message from DDP server is not masked
        if ((ord($text[1]) & ~self::PAYLOAD_LEN_MASK) !== 0) {
            return new FrameStatus(FrameStatus::NOT_VALID);
        }

        // supports only basic control frames : TEXT, CLOSE, PING, PONG
        $opcode = ord($text[0]) & WebSocketClient::OPCODE_MASK;
        if ($opcode !== self::TEXT_FRAME_OPCODE
            && $opcode !== self::CLOSE_FRAME_OPCODE
            && $opcode !== self::PING_FRAME_OPCODE
            && $opcode !== self::PONG_FRAME_OPCODE) {
            return new FrameStatus(FrameStatus::NOT_VALID);
        }

        // valid begin, check for length
        $payload_len_header = ord($text[1]) & WebSocketClient::PAYLOAD_LEN_MASK;
        $header_size = 2;
        $payload_len_size = 0;
        $len = strlen($text) - $header_size;

        // RFC 6455 Section 5.2

        switch ($payload_len_header) {
        default:
            $expectedLen = $payload_len_header;
            break;
        case self::PAYLOAD_SIZE_16BIT_OPCODE:
            $payload_len_size = 2;
            $expectedLen = $header_size + $payload_len_size;
            if ($len < $expectedLen)
                return new FrameStatus(FrameStatus::MISSING_END);

            // 'v' means big endian unsigned 16 bit
            $unpack = unpack('n', substr($text, $header_size, $payload_len_size));
            $expectedLen = current($unpack);
            break;
        case self::PAYLOAD_SIZE_64BIT_OPCODE:
            $payload_len_size = 8;
            $expectedLen = $header_size + $payload_len_size;
            if ($len < $expectedLen)
                return new FrameStatus(FrameStatus::MISSING_END);

            // 'P' means big endian unsigned 64 bit
            $unpack = unpack('J', substr($text, $header_size, $payload_len_size));
            $expectedLen = current($unpack);
            break;
        }



        $len = strlen($text) - $header_size - $payload_len_size;

        if ($len < $expectedLen) {
            return new FrameStatus(FrameStatus::MISSING_END);
        } else if ($len > $expectedLen) {
            $this->setLastExceeds($len - $expectedLen);
            return new FrameStatus(FrameStatus::EXCEED_END);
        }
        return new FrameStatus(FrameStatus::VALID);
    }

    private function setLastExceeds($lastExceeds) {
        $this->lastExceedBytes = $lastExceeds;
    }
}