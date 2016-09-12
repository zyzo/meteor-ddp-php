<?php
namespace zyzo\MeteorDDP;
require 'vendor/autoload.php';
require_once __DIR__ . '/Utils.php';
use zyzo\MeteorDDP\asynccall\ThreadPool;
class DDPClient
{

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
    /**
     * @var ThreadPool
     */
    private $asyncCallPool;
    /**
     * @var MongoAdapter
     */
    private $mongoAdapter;

    /**
     * When creating a DDPClient instance, a Websocket connection will be
     * automatically created. A meteor server should be running at $host:$port
     * @param string $host
     * @param int|null $port
     * @throws \Exception
     */
    public function __construct($host, $port = 3000)
    {
        $address = "{$host}:{$port}";

        $this->sock = new socket\FSocketPipe();
        $this->sock->Open($address);

        $this->sender = new DDPSender($this->sock);
        $this->results = new \Threaded();
        $this->mongoAdapter = new MongoAdapter();

        $handShakeMsg = WebSocketClient::handshakeMessage($address);
        $this->listener = new DDPListener($this, $this->sender, $this->sock);
        $this->listener->Start();

        $this->sock->Write($handShakeMsg);


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
    public function connect($version = 1, $supportedVersions = array(1))
    {
        $this->sender->connect($version, $supportedVersions);
    }

    /**
     * Create a MongoDB connection with provided information. If this function is not
     * called (and succeded), DDP collection data will be ignored.
     * @param $server
     * @param $options
     * @param string $db
     */
    public function connectMongo($server, $options, $db = "meteor")
    {
        $this->mongoAdapter->connect($server, $options, $db);
    }

    /**
     * Synchronous Meteor.call. Use DDPClient::getResult to poll the return value
     * @param $method
     * @param $args
     */
    public function call($method, $args)
    {
        $this->sender->rpc($this->currentId, $method, $args);
        $this->methodMap[$method] = $this->currentId;
        $this->currentId++;
    }

    /**
     * Return the result of the method which has been called.
     * null is returned if the result is not yet available (no answer from server)
     * @param string $method
     *         name of the invoked method
     * @return string the result in json format
     * the result in json format
     * null if no result found
     * @throws \Exception
     */
    function getResult($method)
    {
        $listener = $this->listener;
        if (!$listener->isRunning()) {
            throw new \Exception('Internal error : Socket listener has stopped running');
        }
        $listener->MicroRun();

        $result = null;
        if (array_key_exists($method, $this->methodMap)) {
            $id = $this->methodMap[$method];
            if (isset($this->results->$id)) {
                $result = $this->results->$id;
                unset($this->results->$id);
            }
        }
        return $result;
    }

    /**
     * Asynchronous Meteor.call. $callback method will be called with the result as a parameter
     * @param $method
     * @param $args
     * @param $callback
     */
    public function asyncCall($method, $args, $callback)
    {
        $this->sender->rpc($this->currentId, $method, $args);
        $this->methodMap[$method] = $this->currentId;
        $this->currentId++;
        $this->asyncCallPool->startCall($this, $method, $callback);
    }

    /**
     * @param $name
     * @param array $args
     */
    public function subscribe($name, $args = array()) {
        static $subId = 0;
        $this->sender->sub($subId++, $name, $args);
    }

    public function sender() {
        return $this->sender;
    }

    /**
     * Stop DDP communication and child thread(s). This must be called when the
     * DDP client is done talking to the server
     */
    public function stop()
    {
        $this->listener->Stop();
    }


    /* INTERNAL USE SECTION */

    private static $log = false;
    public static function enableLog() {
        DDPClient::$log = true;
    }


    static function log ($msg) {
        if (DDPClient::$log) {
            echo $msg;
        }
    }
    function onMessage($message)
    {
        DDPClient::log('Receiving ' . json_encode($message) . PHP_EOL);
        if ($message=== null || !isset($message->msg)) {
            return;
        }
        switch ($message->msg) {
            case 'ping' :
                $this->onPing(isset($message->id) ? $message->id : null);
                break;
            case 'result' :
                $this->onResult($message);
                break;
            case 'added' :
                $this->onAdded($message);
                break;
            case 'changed' :
                $this->onChanged($message);
                break;
            case 'removed' :
                $this->onRemoved($message);
                break;
            case 'ready' :
                $this->onReady($message);
                break;
            default :
                break;
        }
    }

    private function onPing($pingId)
    {
        $this->sender->pong($pingId);
    }

    private function onResult($message)
    {
        $this->results[$message->id] = $message->result;
    }

    private function onAdded($message)
    {
        $this->mongoAdapter->insertOrUpdate(
            $message->collection,
            $message->id,
            isset($message->fields) ? $message->fields : null);
    }

    private function onChanged($message)
    {
        $this->mongoAdapter->update(
            $message->collection,
            $message->id,
            isset($message->fields) ? $message->fields : null,
            isset($message->cleared) ? $message->cleared : null);
    }

    private function onRemoved($message)
    {
        $this->mongoAdapter->remove($message->collection, $message->id);
    }

    private function onReady($message)
    {
        // stub
    }

}
