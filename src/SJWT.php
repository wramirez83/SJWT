<?php

namespace Wramirez83\Sjwt;
use Wramirez83\Sjwt\Tools\StructJWT;
use Wramirez83\Sjwt\Tools\UrlEncode;
require ('./../bootstrap.php');

class SJWT{

    protected $header;
    protected $payload;
    protected $secret;

    public function __construct(){
       
        
    }

    public function encode($payload, $exp = 60){
        $this->secret = getenv('SECRET_JWT');
        $this->header = StructJWT::setHeader();
        $this->payload = StructJWT::setPayload($payload, $exp);
        $signature = hash_hmac('sha256', $this->header . '.' . $this->payload, $this->secret, true);
        $base64UrlSignature = UrlEncode::base64UrlEncode($signature);
        return  $this->header . '.' . $this->payload . '.' . $base64UrlSignature;
    }

    
}