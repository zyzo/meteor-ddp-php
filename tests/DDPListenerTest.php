<?php
require_once __DIR__ . '/../src/Utils.php';
require_once __DIR__ . "/../src/WebSocketClient.php";
require_once __DIR__ ."/../src/DDPListener.php";
require_once __DIR__ ."/../src/DDPSender.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);
$host = 'localhost:3000';  //where is the websocket server
$port = 3000;
$local = "http://localhost/";  //url where this script run
$data = '';  //data to be send

$head = "GET /websocket HTTP/1.1" . "\r\n" .
    "Connection: Upgrade" . "\r\n" .
    "Upgrade: websocket" . "\r\n" .
    "Origin: http://localhost" . "\r\n" .
    "Host: $host" . "\r\n" .
    "Sec-WebSocket-Version: 13\r\n" .
    "Sec-WebSocket-Key: Hxu4fCuzO7VK8Evf0oNu4Q==\r\n" .
    "Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits\r\n" .
    "Content-Length: " . strlen($data) . "\r\n" . "\r\n";

//WebSocket handshake
if (!($sock = fsockopen('localhost', 3000, $errno, $errstr, 2))) {
    echo 'Cannot open sock';
    die;
}

print 'WebSocket Handshake' . PHP_EOL;
fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);

$listener = new DDPListener($sock);
$listener->start();

$sender = new DDPSender($sock);
$sender->connect(1, array(1));

sleep(5);
/**
 * For this to work, server meteor should declare following remote method
     Meteor.methods({
        foo : function (arg) {
            check(arg, Number);
            if (arg == 1) {
                return 42;
            }
            return -1;
        }
    });
 *
 */
$sender->rpc('foo', array('1'));

while(true) {
    sleep(100);
    //$sender->ping();
}