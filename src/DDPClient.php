<?php

class DDPClient {

    /**
     * @var DDPSender
     */
    private $sender;

    /**
     * @var DDPListener
     */
    private $listener;
    /**
     * @var resource
     */
    private $sock;
    /**
     * @var array
     */
    private $methodMap;
    /**
     * @var Threaded
     */
    private $results;
    /**
     * @var int
     */
    private $currentId;

    /**
     * When creating a DDPClient instance, a DDP connection will be
     * automatically created. A meteor server should be running at $host:$port
     * @param string $host
     * @param int|null $port
     */
    public function __construct($host, $port = 3000)
    {
        $errno = 0;
        $errstr = 'Error connecting to Meteor server';
        $this->sock = fsockopen($host, $port, $errno, $errstr, 10);
        $this->sender = new DDPSender($this->sock);
        $this->results = new Threaded();
        $this->listener = new DDPListener($this, $this->sock);
        $this->listener->start();
        $handShakeMsg =  \zyzo\WebSocketClient::handshakeMessage($host . ':' . $port);
        fwrite($this->sock, $handShakeMsg) or die('error:' . $errno . ':' . $errstr);
        $this->currentId = 0;
        $this->methodMap = array();
    }

    public function connect($version = 1, $supportedVersions = array(1)) {
        $this->sender->connect($version, $supportedVersions);
    }

    public function checkConnection()
    {

    }

    function call($method, $args) {
        $this->sender->rpc($this->currentId, $method, $args);
        $this->methodMap[$method] = $this->currentId;
        $this->currentId++;
    }

    /**
     * @param $method
     * @return string the result in json format
     *         return null if no result found
     */
    function getResult($method) {
        if (array_key_exists($method, $this->methodMap)) {
            $id = $this->methodMap[$method];
            $result = isset($this->results->$id) ? $this->results[$id] : null;
            return $result;
        }
        return null;
    }

    public function onMessage($message)
    {
        if ($message !== null && isset($message->msg)) {
            switch ($message->msg) {
                case 'ping' :
                    $this->onPing();
                    break;
                case 'result' :  // rpc method
                    $this->onResult($message);
                    break;
                default :
                    //echo 'Unknown message ! ' . PHP_EOL;
            }
        }
    }

    private function onPing()
    {
        $this->sender->pong();
    }

    private function onResult($message) {
        $this->results[$message->id] = $message->result;
    }

    function stop()
    {
        $this->listener->kill();
    }

}