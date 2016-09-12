<?php
namespace zyzo\MeteorDDP;

use zyzo\MeteorDDP\enum\FrameStatus;

class DDPListener {

    private $sock;
    private $sender;
    private $wsClient;

    private $unparsedBytes;

    /**
     * @param $client DDPClient
     * @param $sender DDPSender
     * @param $sock resource
     */
    public function __construct($client, $sender, $sock) {
        $this->client = $client;
        $this->sock = $sock;
        $this->sender = $sender;
        $this->wsClient = new WebSocketClient();
        $this->unparsedBytes = null;
    }

    public function run() {
      while ($this->isRunning())
        $this->micro_run();
    }

    public function micro_run() {
      $data = fread($this->sock, 20000);
      $this->process($data);
    }

    public function isRunning() {
      $res = !feof($this->sock);
      return $res;
    }

    /**
     * @param $data
     * @return bool
     */
    private function process($data) {
        if ($data === null || strlen($data) < 2) {
            return false;
        }
        $success = false;
        $wsClient = $this->wsClient;
        $status = $wsClient->validFrame($data);
        switch ($status->value()) {
            case (FrameStatus::NOT_VALID) :
                if ($this->unparsedBytes !== null) {
                    $success = $this->process($this->unparsedBytes . $data);
                    if ($success) {
                        $this->unparsedBytes = null;
                    }
                } else {
                    $this->process(substr($data, 1));
                }
                break;
            case (FrameStatus::MISSING_END) :
                $this->unparsedBytes = $data;
                break;
            case (FrameStatus::VALID) :
                $success = $this->processValidFrame($data);
                if (!$success) {
                    $this->process(substr($data, 1));
                }
                break;
            case (FrameStatus::EXCEED_END) :
                // doesn't work if invoke $this->wsClient->lastExceeds
                $exceedBytesNum = $wsClient->lastExceeds();
                $success = $this->processValidFrame(substr($data, 0, strlen($data) - $exceedBytesNum));
                if ($success) {
                    $exceedBytes = substr($data, strlen($data) - $exceedBytesNum);
                    $this->process($exceedBytes);
                } else {
                    $this->process(substr($data, 1));
                }
                break;
        }
        return $success;
    }

    /**
     * @param $data
     * @return bool
     *       true if process successful
     */
    private function processValidFrame($data)
    {
        $json = $this->wsClient->draft10Decode($data);
        $parsed = json_decode($json->payload);
        if (json_last_error() === JSON_ERROR_NONE) {
            try {
                $this->client->onMessage($parsed);
            } catch (\Exception $e) {
                Utils::_error_log($e->getMessage() . PHP_EOL);
            }
        } else {
            return false;
        }
        return true;
    }
}
