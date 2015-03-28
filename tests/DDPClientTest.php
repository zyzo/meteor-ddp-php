<?php
require_once __DIR__ . "/../src/WebSocketClient.php";
require_once __DIR__ ."/../src/DDPListener.php";
require_once __DIR__ ."/../src/DDPSender.php";
require_once __DIR__ ."/../src/DDPClient.php";

$client = new DDPClient('localhost', 3000);
$client->connect();
$client->call("foo", array(1));
while(($yo = $client->getResult("foo")) === null) { sleep(5);};
echo 'Result = ' . $yo . PHP_EOL;

$client->stop();