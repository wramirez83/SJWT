<?php

namespace Wramirez83\Sjwt\Tools;

class UrlEncode{
    public static function base64UrlEncode($text){
        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode($text)
        );
    }
}