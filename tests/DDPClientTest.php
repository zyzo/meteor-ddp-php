<?php
namespace zyzo\MeteorDDP\tests;
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);
$client->connect();
$client->call("foo", array(1));
while(($yo = $client->getResult("foo")) === null) { sleep(1);};
echo 'Result = ' . $yo . PHP_EOL;

function resultHandler($a) {
    echo 'Result = ' . $a . PHP_EOL;
}


$client->asyncCall("foo", array(1), function($a) {
    resultHandler($a);
});
echo 'Do some work...' . PHP_EOL;

$client->asyncCall("foo", array(1), 'zyzo\MeteorDDP\tests\resultHandler');
echo 'Do some work...' . PHP_EOL;

$client->stop();