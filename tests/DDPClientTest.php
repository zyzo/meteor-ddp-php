<?php
namespace zyzo\MeteorDDP\tests;
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);
$client->connect();
$client->call("foo", array(1));
while(($yo = $client->getResult("foo")) === null) { sleep(2);};
echo 'Result = ' . $yo . PHP_EOL;

$client->stop();