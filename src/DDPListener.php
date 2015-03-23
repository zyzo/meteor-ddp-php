<?php

class DDPListener extends Thread{

    private $sock;
    private $sender;

    /**
     * @param $sock
     * @param $eventListener EventListener
     */
    public function __construct($sock, $eventListener) {
        $this->sock = $sock;
        $this->sender = new DDPSender($sock);
    }

    public function run() {
        while(!feof($this->sock)) {
            $data = fread($this->sock, 20000);
            $this->process($data);
        }
    }

    private function process($data) {
        $json = \zyzo\WebSocketClient::draft10Decode($data);
        echo 'Receiving ' . $json . PHP_EOL;
        $parsed = json_decode($json);
        if ($parsed !== null && isset($parsed->msg)) {
            switch ($parsed->msg) {
                case 'ping' :
                    $this->sender->pong();
                    break;
                case 'updated' :  // rpc method
                    echo 'Haha, result is ' . $json . PHP_EOL;
                default :
                    echo 'Unknow message ! ' . PHP_EOL;
            }
        }
    }
}