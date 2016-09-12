<?php
namespace zyzo\MeteorDDP\pump;

class PThreadsPump extends \Thread {
  private $pump;
  private $stored_data = [];

  public function __construct() {
    $reflection = new \ReflectionClass("zyzo\MeteorDDP\pump\BasePump");
    $this->pump = $reflection->newInstanceArgs(func_get_args());
  }

  public function run() {
    while ($this->pump->isRunning()) {
      $data = $this->pump->MicroRun();
      array_push($this->stored_data, $data);
    }
  }

  public function MicroRun() {
    $chunk = [];

    while (count($this->stored_data)) {
      // It is performance consuming but at least thread safe
      array_push($chunk, array_shift($this->stored_data));
    }

    return implode("", $chunk);
  }

  public function isRunning() {
    $thread_running = $this->isRunning();
    $pump_running = $this->pump->isRunning();

    if ($thread_running == $pump_running)
      return $thread_running || $pump_running;

    if (!$thread_running) {
      throw new \Exception("Internal error : Pump thread died");
    }

    // De-sync state, completely stop
    $this->pump->Stop();

    return false;
  }

  public function __call($method, $args) {
    return call_user_func_array([$this->pump, $method], $args);
  }

  public function Stop() {
    $this->Kill();
    $this->pump->Stop();
  }
}
