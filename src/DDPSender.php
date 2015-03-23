<?php
class DDPSender {

    private $sock;

    public function __construct($sock)
    {
        $this->sock = $sock;
    }

    public function connect($version, $supported) {
        $this->send('
            {"msg":"connect","version":"'. $version . '","support": ' .
            $this->arrayToString($supported, true). '}', 'text'
        );
    }

    public function ping() {
        $this->send(
            '{"msg":"ping"}'
        );
    }

    public function pong() {
        $this->send(
            '{"msg":"pong"}'
        );
    }

    public function rpc($method, $args) {
        $this->send(
            '{"msg":"method","method":"' . $method .
            '","params":' . $this->arrayToString($args) . ',"id":"1"}'
        );
    }

    private function arrayToString($args, $isText = false)
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

    private function send($msg)
    {
        echo 'Sending ' . $msg . PHP_EOL;
        $msg = \zyzo\WebSocketClient::draft10Encode($msg, true);
        fwrite($this->sock, $msg);
    }

}