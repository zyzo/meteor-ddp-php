<?php
/**
 * Created by PhpStorm.
 * User: zyzo
 * Date: 30/04/15
 * Time: 19:11
 */

namespace zyzo\MeteorDDP\asynccall;


class ThreadPool {

    /**
     * The default maximum async threads of node.js is 4. So why 1000 ?
     * Maybe it's the best for the user to decide (via some future method $ddpClient->setMaxAsyncThreads)
     * PR is appreciated.
     */
    const POOL_SIZE = 1000;
    private $pool;
    private $available;

    public function __construct() {
        for ($i = 0; $i < ThreadPool::POOL_SIZE; $i++) {
            $this->available[$i] = true;
        }
    }

    public function startCall($ddpClient, $method, $callback) {
        $found = false;
        for ($i = 0; $i < ThreadPool::POOL_SIZE; $i++) {
            if ($this->available[$i]) {
                $this->available[$i] = false;
                $this->pool[$i] = new ResultPolling($ddpClient, $method, $callback, $this, $i);
                $this->pool[$i]->start();
                $found = true;
                break;
            }
        }
        if (!$found) {
            var_dump($this->available);
            throw new \Exception("No more asyncCall thread available in pool");
        }
    }


    public function freeThread($threadId) {
        $this->pool[$threadId] = null;
        $this->available[$threadId] = true;
    }


}