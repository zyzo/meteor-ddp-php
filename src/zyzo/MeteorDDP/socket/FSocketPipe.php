<?php
namespace zyzo\MeteorDDP\socket;

require('AbstractSocketPipe.php');

class FSocketPipe extends AbstractSocketPipe
{
  private $sock;

  public function open($address) {
    list($host, $port) = explode(':', $address);

    $errno = 0;
    $errstr = 'Error connecting to Meteor server';
    $this->sock = fsockopen($host, $port, $errno, $errstr, AbstractSocketPipe::$timeout);

    if (!$this->sock) {
      throw new \Exception('Error connecting to Meteor server');
    }
  }

  public function Close() {
    if (!$this->IsClosed())
      return;

    fclose($this->sock);
    $this->sock = null;
  }

  public function Write($data) {
    if (!$this->IsValid())
      return;

    $result = fwrite($this->sock, $data);

    if ($result === false) {
      $this->Close();
      throw new \Exception('Socket write error! ' . PHP_EOL);
    }

    if ($result != strlen($data))
      throw new \Exception('TODO: Socket buffer is full ' . PHP_EOL);

    return true;
  }

  public function Read($chunk_size = AbstractSocketPipe::CHUNK_SIZE) {
    return fread($this->sock, $chunk_size);
  }

  public function IsValid() {
    if ($this->IsClosed())
      return false;

    return !feof($this->sock);
  }

  public function IsClosed() {
    return $this->sock === null;
  }
}
