<?php

namespace App\Libraries;

class Utility
{
    public static function encrypt($string, $key)
    {
        $result = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            // $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $keychar = substr($key, bcmod($i, strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }

        return base64_encode($result);
    }

    public static function decrypt($string, $key)
    {
        if (self::isPlainText($string)) {
            return $string;
        }

        $result = '';
        $string = base64_decode($string);

        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            // $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $keychar = substr($key, bcmod($i, strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }

    private static function isPlainText($string)
    {
        return ctype_digit($string) || ctype_alpha($string) || ctype_alnum($string);
    }
}
