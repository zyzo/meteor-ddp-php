<?php
namespace zyzo\MeteorDDP\asynccall;

class ResultPolling extends \Thread {

    /**
     * @var DDPClient
     */
    private $ddpClient;

    /**
     * Thread pool that this thread lives in
     * @var ThreadPool
     */
    private $threadPool;
    /**
     * Id of the thread inside the thread pool
     * @var
     */
    private $threadId;
    /**
     * @param $ddpClient
     * @param $method
     * @param $callback
     */
    public function __construct($ddpClient, $method, $callback, $threadPool, $threadId) {
        $this->ddpClient = $ddpClient;
        $this->method = $method;
        $this->callback = $callback;
        $this->threadPool = $threadPool;
        $this->threadId = $threadId;
    }

    public function run() {
        while(($result = $this->ddpClient->getResult($this->method)) === null) {}
        call_user_func_array($this->callback, array($result));
        $this->threadPool->freeThread($this->threadId);
    }

    public function setDdpClient($ddpClient)  { $this->ddpClient = $ddpClient;}
    public function setMethod($method)  { $this->method = $method;}
    public function setCallback($callback)  { $this->callback = $callback;}
}