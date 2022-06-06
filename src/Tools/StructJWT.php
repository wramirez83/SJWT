<?php
namespace Wramirez83\Sjwt\Tools;

class StructJWT{
    
    public static function setHeader($header = [
        'typ' => 'JWT',
        'alg' => 'HS256']){
            return UrlEncode::base64UrlEncode(json_encode($header));
    }

    public static function setPayload($payload = [], $exp){
            $payload['exp'] = time() + $exp;
            return UrlEncode::base64UrlEncode(json_encode($payload));
    }

}