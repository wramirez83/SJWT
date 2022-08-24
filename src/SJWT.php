<?php

namespace Wramirez83\Sjwt;
use Wramirez83\Sjwt\Tools\StructJWT;
use Wramirez83\Sjwt\Tools\UrlEncode;
use Carbon\Carbon;

class SJWT{

    protected $tokenExpired;
    protected $signatureValid;

    public function __construct(){
       
        
    }

    public static function encode($payload, $exp = 60){
        try{
            $secret = getenv('SECRET_JWT');
            $header = StructJWT::setHeader();
            $payload = StructJWT::setPayload($payload, $exp);
            $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
            $base64UrlSignature = UrlEncode::base64UrlEncode($signature);
            return  $header . '.' . $payload . '.' . $base64UrlSignature;
        }catch(\Exception $e){
            return $e;
        }
    }

    public static function decode($jwt = '.', $listJwt = 'Authorization', $type = 1){
        try{
            $secret = getenv('SECRET_JWT');
            if($jwt == '.'){
                $headerApache = apache_request_headers();
                $beader = explode(' ', $headerApache['Authorization']);
                $jwt = $beader[$type];
            }
            // split the token
            $tokenParts = explode('.', $jwt);
            $header = base64_decode($tokenParts[0]);
            $payload = base64_decode($tokenParts[1]);
            $signatureProvided = $tokenParts[2];
    
            // check the expiration time - note this will cause an error if there is no 'exp' claim in the token
            $expiration = Carbon::createFromTimestamp(json_decode($payload)->exp);
            $tokenExpired = (Carbon::now()->timezone('America/Bogota')->diffInSeconds($expiration, false) < 0);
    
            // build a signature based on the header and payload using the secret
            $base64UrlHeader = UrlEncode::base64UrlEncode($header);
            $base64UrlPayload = UrlEncode::base64UrlEncode($payload);
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
            $base64UrlSignature = UrlEncode::base64UrlEncode($signature);
    
            // verify it matches the signature provided in the token
            $signatureValid = ($base64UrlSignature === $signatureProvided);
    
            return [
                json_decode($header),
                json_decode($payload),
                $signatureProvided,
                $signatureValid,
                $tokenExpired
            ];
        }catch(\Exception $e){
            return $e;
        }

    }
}