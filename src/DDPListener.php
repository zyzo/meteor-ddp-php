<?php

class DDPListener extends Thread{

    private $sock;
    private $sender;

    /**
     * @param $client DDPClient
     * @param $sock resource
     */
    public function __construct($client, $sock) {
        $this->client = $client;
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
        $jsons = \zyzo\WebSocketClient::draft10Decode($data);
        foreach ($jsons as $json) {
            // echo 'Receiving ' . $json . PHP_EOL;
            $parsed = json_decode($json);
            $this->client->onMessage($parsed);
        }
    }
}