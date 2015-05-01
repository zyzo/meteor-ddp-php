<?php
require __DIR__ . '/../vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$client = new DDPClient('localhost', 3000);
$client->connectMongoDB('localhost:27017', array(), 'test');
$client->connect();
while (1) {
    sleep(2);
}