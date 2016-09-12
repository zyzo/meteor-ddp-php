<?php
namespace zyzo\MeteorDDP\socket;

abstract class AbstractSocketPipe
{
  const CHUNK_SIZE = 20000;
  public static $timeout = 10;

  abstract public function Open($address);
  abstract public function Close();

  abstract public function Write($data);
  abstract public function Read($limit = AbstractSocketPipe::CHUNK_SIZE);

  abstract public function IsValid();
  abstract public function IsClosed();
}
