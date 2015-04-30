<?php
namespace zyzo\MeteorDDP;
require __DIR__ . '/../../../vendor/autoload.php';
use zyzo\MeteorDDP\asynccall\ResultPolling;
use zyzo\MeteorDDP\asynccall\ThreadPool;

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
     * @var \Threaded
     */
    private $results;
    /**
     * @var int
     */
    private $currentId;
    private $asyncCallPool;
    /**
     * When creating a DDPClient instance, a Websocket connection will be
     * automatically created. A meteor server should be running at $host:$port
     * @param string $host
     * @param int|null $port
     * @throws \Exception
     */
    public function __construct($host, $port = 3000)
    {
        $errno = 0;
        $errstr = 'Error connecting to Meteor server';
        $this->sock = fsockopen($host, $port, $errno, $errstr, 10);
        $this->sender = new DDPSender($this->sock);
        $this->results = new \Threaded();

        $handShakeMsg =  WebSocketClient::handshakeMessage($host . ':' . $port);
        $this->listener = new DDPListener($this, $this->sender, $this->sock);
        if (fwrite($this->sock, $handShakeMsg) === false) {
            throw new \Exception('error:' . $errno . ':' . $errstr);
        }
        $this->listener->start();
        $this->currentId = 0;
        $this->methodMap = array();
        $this->asyncCallPool = new ThreadPool();
    }

    /**
     * This function creates a DDP connection on top of the WebSocket channel.
     * This must be called before the client could invoke server's method.
     * @param int $version
     * @param array $supportedVersions
     */
    public function connect($version = 1, $supportedVersions = array(1)) {
        $this->sender->connect($version, $supportedVersions);
    }

    public function checkConnection() {}

    function call($method, $args) {
        $this->sender->rpc($this->currentId, $method, $args);
        $this->methodMap[$method] = $this->currentId;
        $this->currentId++;
    }

    /**
     * @param $method
     * @param $args
     * @param $callback
     */
    function asyncCall($method, $args, $callback) {
        $this->sender->rpc($this->currentId, $method, $args);
        $this->methodMap[$method] = $this->currentId;
        $this->currentId++;
        $this->asyncCallPool->startCall($this, $method, $callback);
    }

    /**
     * @param string $method
     *         name of the invoked method
     * @return string the result in json format
     * the result in json format
     * null if no result found
     * @throws \Exception
     */
    function getResult($method) {
        $listener = $this->listener;
        if (!$listener->isRunning()) {
            throw new \Exception('Internal error : Socket listener has stopped running');
        }
        $result = null;
        if (array_key_exists($method, $this->methodMap)) {
            $id = $this->methodMap[$method];
            if (isset($this->results->$id)) {
                $result = isset($this->results->$id) ? $this->results->$id : null;
                unset($this->results->$id);
            }
        }
        return $result;
    }

    function onMessage($message)
    {
        if ($message !== null && isset($message->msg)) {
            //echo 'Receiving ' ; print_r($message); echo PHP_EOL;
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
        } else {
            // echo 'Unknown message ! ' . PHP_EOL;
        }
    }

    private function onPing()
    {
        $this->sender->pong();
    }

    private function onResult($message) {
        $this->results[$message->id] = $message->result;
    }

    /**
     * Stop DDP communication and child thread(s). This must be called when the
     * DDP client is done talking to the server
     */
    public function stop()
    {
        $this->listener->kill();
    }
}