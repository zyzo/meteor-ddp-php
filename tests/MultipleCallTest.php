<?php
namespace zyzo\MeteorDDP\tests;
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);
$client->connect();

for ($i = 0; $i < 100; $i++) {
    $client->call("foo2", array(1));
    while (($yo = $client->getResult("foo2")) === null) {
    };
    echo 'Result = ' . $yo . PHP_EOL;
}
$client->stop();