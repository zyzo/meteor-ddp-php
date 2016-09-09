<?php
require 'vendor/autoload.php';
use zyzo\MeteorDDP\DDPClient;
ini_set('display_errors', 'On');
error_reporting(E_ALL);
DDPClient::enableLog();

$client = new DDPClient('localhost', 3000);

// Must connect to local mongodb before connecting to meteor
$client->connectMongo('localhost:27017', array(), 'test');

// Connect to meteor
$client->connect();

// Subscribe to function Foo
$client->subscribe('Foo', array());

while (1) {}
