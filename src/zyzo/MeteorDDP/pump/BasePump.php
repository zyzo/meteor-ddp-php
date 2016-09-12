<?php
namespace zyzo\MeteorDDP\pump;

class BasePump {
    private $sock;

    public function __construct($sock) {
      $this->sock = $sock;
    }

    public function MicroRun() {
      return $this->sock->Read();
    }

    public function isRunning() {
      return $this->sock->IsValid();
    }

    public function Start() {
    }

    public function Stop() {
    }
}
