<?php

namespace AE97\Panel;

class Utilities {

    function generate_string($length) {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        $count = strlen($charset);
        for ($i = 0; $i < $length; $i++) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }
        return $str;
    }

    public static function logError($ex) {
        error_log($ex instanceof \Exception ? $ex->getMessage() : $ex . "\r");
    }

    public static function generateGUID() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8) . $hyphen
                  . substr($charid, 8, 4) . $hyphen
                  . substr($charid, 12, 4) . $hyphen
                  . substr($charid, 16, 4) . $hyphen
                  . substr($charid, 20, 12);
            return $uuid;
        }
    }

}
