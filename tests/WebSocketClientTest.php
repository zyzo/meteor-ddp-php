<?php
require_once "../src/Utils.php";
require_once "../src/WebSocketClient.php";

echo '<h2>Unmasked frame</h2>';
hex_dump(\zyzo\WebSocketClient::hybi10Encode('Hello', 'text', false));
echo '<br/>Expected : <br/>';
echo '&nbsp&nbsp&nbsp&nbsp 81 05 48 65 6c 6c 6f (contains "Hello")';
echo '<h2>Masked frame</h2>';
hex_dump(\zyzo\WebSocketClient::hybi10Encode('Hello', 'text', true));
echo '<h2>Masked frame with known key</h2>';
/**
 * @param $hexKey
 * @param $maskKey
 * @return mixed
 */
function convertHexToMaskKey($hexKey) {
    echo 'Mask key : ';
    $maskKey = array();
    for ($i = 0; $i < 4; $i++) {
        $maskKey[$i] = chr(hexdec($hexKey[$i]));
        echo $hexKey[$i] . ' ';
    }
    return $maskKey;
}
$maskKey = convertHexToMaskKey(array('37', 'fa', '21', '3d'));
echo '<br/>';
hex_dump(\zyzo\WebSocketClient::hybi10Encode('Hello', 'text', true, $maskKey));
echo '<br/>Expected : <br/>';
echo '&nbsp&nbsp&nbsp&nbsp 81 85 37 fa 21 3d 7f 9f 4d 51 58';


echo '<h2>DDP Connection</h2>';
$maskKey = convertHexToMaskKey(array('6b', '5c', '75', '7f'));
echo '<br/>';
hex_dump(\zyzo\WebSocketClient::hybi10Encode('{"msg" : "connect", "version" : "1", "support" : ["1"]}', 'text', true, $maskKey) , '<br/>');
echo '<br/>Expected : <br/>';

echo '0000   10 7e 18 0c 0c 7e 55 45 4b 7e 16 10 05 32 10 1c  .~...~UEK~...2..<br/>
0010   1f 7e 59 5f 49 2a 10 0d 18 35 1a 11 49 7c 4f 5f  .~Y_I*...5..I|O_<br/>
0020   49 6d 57 53 4b 7e 06 0a 1b 2c 1a 0d 1f 7e 55 45  ImWSK~...,...~UE<br/>
0030   4b 07 57 4e 49 01 08                             K.WNI..<br/>';
