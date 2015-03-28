<?php
namespace zyzo\MeteorDDP;

class Utils
{
    /**
     * Pretty print data as hex thanks to
     * http://stackoverflow.com/questions/1057572/how-can-i-get-a-hex-dump-of-a-string-in-php
     * @param $data
     * @param string $newline
     */
    public static function hex_dump($data, $newline = "\n")
    {
        static $from = '';
        static $to = '';

        static $width = 16; # number of bytes per line

        static $pad = '.'; # padding for non-visible characters

        if ($from === '') {
            for ($i = 0; $i <= 0xFF; $i++) {
                $from .= chr($i);
                $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
            }
        }

        $hex = str_split(bin2hex($data), $width * 2);
        $chars = str_split(strtr($data, $from, $to), $width);

        $offset = 0;
        foreach ($hex as $i => $line) {
            $info = sprintf('%6X', $offset) . ' : ' . implode(' ', str_split($line, 2)) . ' [' . $chars[$i] . ']' . $newline;
            echo html_entity_decode($info);
            $offset += $width;
        }
    }
}
