<?php
require_once __DIR__ . "/../src/WebSocketClient.php";
require_once __DIR__ ."/../src/DDPListener.php";
require_once __DIR__ ."/../src/DDPSender.php";
require_once __DIR__ ."/../src/DDPClient.php";

$client = new DDPClient('localhost', 3000);
$client->connect();
$client->call("foo", array(1));
while(($a = $client->getResult("foo")) === null) {};
echo 'Result = ' . $a . PHP_EOL;
$client->call("foo", array(2));
while(($b = $client->getResult("foo")) === null) {};
echo 'Result = ' . $b . PHP_EOL;
$client->stop();