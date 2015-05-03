<?php
namespace zyzo\MeteorDDP;

class DDPSender extends \Threaded {

    private $sock;

    public function __construct($sock)
    {
        $this->sock = $sock;
    }

    public function connect($version, $supported) {
        $this->send(
            '{"msg":"connect","version":"'. $version . '","support": ' .
            $this->arrayToString($supported, true). '}', 'text'
        );
    }

    public function ping() {
        $this->send(
            '{"msg":"ping"}'
        );
    }

    public function pong($pingId) {
        $this->send(
            '{' .
            ($pingId != null ? '"id:"' . $pingId . ',' : '') .
            '"msg":"pong"}'
        );
    }

    public function rpc($id, $method, $args) {
        $this->send(
            '{"msg":"method","method":"' . $method .
            '","params":' . $this->arrayToString($args) . ',"id":"' . $id . '"}'
        );
    }

    public function sub($id, $name, $args)
    {
        $this->send(
            '{"msg":"sub","name":"' . $name . '"' .
            ($args !== null && count($args) > 0 ? '","params":' . $this->arrayToString($args) : '') .
            ',"id":"' . $id . '"}'
        );
    }

    function arrayToString($args, $isText = false)
    {
        $arrayLen = count($args);
        if ($arrayLen === 0) {
            return null;
        } else {
            $arrayStr = '[';
            for ($i = 0; $args !== null && $i < $arrayLen; $i++) {
                if ($isText)
                    $arrayStr .= '"' . $args[$i] . '"';
                else
                    $arrayStr .= $args[$i];
                if ($i !== $arrayLen - 1)
                    $arrayStr .= ',';
            }
            $arrayStr .= ']';
            return $arrayStr;
        }
    }

    function send($msg)
    {

        DDPClient::log('Sending ' . $msg . PHP_EOL);
        $msg = WebSocketClient::draft10Encode($msg, 'text', true);
        if (!fwrite($this->sock, $msg)) {
            throw new \Exception('Socket write error! ' . PHP_EOL);
        }
    }

}