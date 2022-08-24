<?php
namespace Wramirez83\Sjwt\Tools;
use Carbon\Carbon;

class StructJWT{
    
    public static function setHeader($header = [
        'typ' => 'JWT',
        'alg' => 'HS256']){
            return UrlEncode::base64UrlEncode(json_encode($header));
    }

    public static function setPayload($payload = [], $exp = 3600){
            $payload['exp'] = strtotime(Carbon::now()->timezone('America/Bogota')->addSeconds($exp));
            return UrlEncode::base64UrlEncode(json_encode($payload));
    }

}