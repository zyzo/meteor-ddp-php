<?php
namespace zyzo\MeteorDDP\tests;
require 'vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);
$client->connect();

function resultHandler($yo) {
    echo 'Async result = ' . $yo . PHP_EOL;
}

for ($i = 0; $i < 100; $i++) {
    $client->asyncCall("foo2", array(1), function($a) {
        resultHandler($a);
    });
}
$client->stop();
