<?php
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;

$client = new DDPClient('localhost', 3000);
$client->connect();
function resultHandler($a) {
    $i = 10000000;
    while ($i--);
    echo 'Result = ' . $a . PHP_EOL;
}
// anonymous function style*//*
$client->asyncCall("foo", array(1), function($a) {
    resultHandler($a);
});
echo 'Doing some work..' . PHP_EOL;
echo 'Doing some work..' . PHP_EOL;
echo 'Doing some work..' . PHP_EOL;

$client->asyncCall("foo", array(1), 'resultHandler');
print_r("Doing some work");
echo 'Doing some work..' . PHP_EOL;
echo 'Doing some work..' . PHP_EOL;
echo 'Doing some work..' . PHP_EOL;

$i = 100000000;
while ($i--);
$client->stop();