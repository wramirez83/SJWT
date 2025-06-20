<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt;

use Carbon\Carbon;
use Throwable;

class UrlEncode
{
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string|false
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

class StructJWT
{
    public static function setHeader(): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        return UrlEncode::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
    }

    public static function setPayload(array $payload, int $expMinutes): string
    {
        $payload['iat'] = Carbon::now()->timestamp;
        $payload['exp'] = Carbon::now()->addMinutes($expMinutes)->timestamp;
        return UrlEncode::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
}

class SJWT
{
    protected bool $tokenExpired = false;
    protected bool $signatureValid = false;

    public static function encode(array $payload, int $exp = 60): string
    {
        $secret = $_ENV['SECRET_JWT'] ?? getenv('SECRET_JWT');
        if (!$secret) {
            throw new \RuntimeException('JWT secret is not set.');
        }

        $header = StructJWT::setHeader();
        $payloadEncoded = StructJWT::setPayload($payload, $exp);
        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $secret, true);
        $base64UrlSignature = UrlEncode::base64UrlEncode($signature);

        return "$header.$payloadEncoded.$base64UrlSignature";
    }

    public static function decode(string $jwt = '.', string $listJwt = 'Authorization', int $type = 1): object
    {
        try {
            $secret = $_ENV['SECRET_JWT'] ?? getenv('SECRET_JWT');
            if (!$secret) {
                throw new \RuntimeException('JWT secret is not set.');
            }

            if ($jwt === '.') {
                $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
                if (!isset($headers[$listJwt])) {
                    throw new \RuntimeException("Header '$listJwt' not found.");
                }
                $parts = explode(' ', $headers[$listJwt]);
                $jwt = $parts[$type] ?? throw new \RuntimeException("Invalid token format in header.");
            }

            $tokenParts = explode('.', $jwt);
            if (count($tokenParts) !== 3) {
                throw new \InvalidArgumentException('Invalid JWT format.');
            }

            [$headerEncoded, $payloadEncoded, $signatureProvided] = $tokenParts;

            $headerJson = UrlEncode::base64UrlDecode($headerEncoded);
            $payloadJson = UrlEncode::base64UrlDecode($payloadEncoded);

            if (!$headerJson || !$payloadJson) {
                throw new \RuntimeException('Invalid base64 encoding in JWT.');
            }

            $header = json_decode($headerJson, false, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($payloadJson, false, 512, JSON_THROW_ON_ERROR);

            if (!isset($payload->exp)) {
                throw new \UnexpectedValueException('Missing "exp" in payload.');
            }

            $expiration = Carbon::createFromTimestamp((int)$payload->exp);
            $now = Carbon::now('America/Bogota');
            $tokenExpired = $now->greaterThan($expiration);

            $reEncodedHeader = UrlEncode::base64UrlEncode($headerJson);
            $reEncodedPayload = UrlEncode::base64UrlEncode($payloadJson);
            $expectedSignature = hash_hmac('sha256', "$reEncodedHeader.$reEncodedPayload", $secret, true);
            $expectedBase64Signature = UrlEncode::base64UrlEncode($expectedSignature);
            $signatureValid = hash_equals($expectedBase64Signature, $signatureProvided);

            return (object)[
                'header' => $header,
                'payload' => $payload,
                'signature' => $signatureProvided,
                'signatureValid' => $signatureValid,
                'tokenExpired' => $tokenExpired,
            ];
        } catch (Throwable $e) {
            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
