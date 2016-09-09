<?php
namespace zyzo\MeteorDDP\examples;
use zyzo\MeteorDDP\DDPClient;
require 'vendor/autoload.php';

$client = new DDPClient('localhost', 3000);
$client->connect();
$client->call("foo", array(1));
while(($a = $client->getResult("foo")) === null) {}
echo 'Result = ' . $a . PHP_EOL;
$client->call("foo", array(2));
while(($b = $client->getResult("foo")) === null) {}
echo 'Result = ' . $b . PHP_EOL;
$client->stop();
