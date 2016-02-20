<?php
namespace zyzo\MeteorDDP\tests;
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;
DDPClient::enableLog();
$client = new DDPClient('localhost', 3000);
$client->connect();

// wait for a long time
// Experimentally it's between 25 to 30s maximum before the socket times out
sleep(30);

$client->call("foo", array(1));
while(($yo = $client->getResult("foo")) === null) { sleep(2);};
echo 'Result = ' . $yo . PHP_EOL;

$client->stop();