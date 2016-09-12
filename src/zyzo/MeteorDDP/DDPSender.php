<?php
namespace zyzo\MeteorDDP;

class DDPSender extends \Threaded {

    private $sock;

    public function __construct($sock)
    {
        $this->sock = $sock;
    }

    public function connect($version, $supported = []) {
        if (empty($supported)) {
            $version = [$supported];
        }

        $packet = [
            "msg" => "connect",
            "version" => $version,
            "support" => $supported,
        ];

        $this->send($packet);
    }

    public function ping() {
        $packet = [
            "msg" => "ping",
        ];

        $this->send($packet);
    }

    public function pong($pingId = '') {
        $packet = [
            "msg" => "pong",
            "id" => $pingId,
        ];

        $this->send($packet);

    }

    public function rpc($id, $method, $args = []) {
        $packet = [
            "msg" => "method",
            "method" => $method,
            "params" => $args,
            "id" => $id,
        ];

        $this->send($packet);
    }

    public function sub($id, $name, $args = null)
    {
        $packet = [
            "msg" => "sub",
            "name" => $name,
            "id" => $id,
        ];

        if (!empty($args))
            $packet['params'] = $args;

        $this->send($packet);
    }

    function arrayToString($args, $isText = false)
    {
        $arrayLen = count($args);
        if ($arrayLen === 0) {
            return null;
        } else {
            return json_encode($args);
        }
    }

    function send($msg)
    {
        if (is_array($msg))
            $msg = $this->arrayToString($msg);

        DDPClient::log('Sending ' . $msg . PHP_EOL);
        $msg = WebSocketClient::draft10Encode($msg, 'text', true);
        if (!fwrite($this->sock, $msg)) {
            throw new \Exception('Socket write error! ' . PHP_EOL);
        }
    }

}
