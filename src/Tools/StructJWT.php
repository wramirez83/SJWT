<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tools;

use Carbon\Carbon;

/**
 * JWT Structure Builder
 * 
 * Handles the creation of JWT header and payload structures
 */
class StructJWT
{
    /**
     * Create and encode JWT header
     * 
     * @param array<string, string> $header Optional custom header. Defaults to standard JWT header
     * @return string Base64URL encoded header
     */
    public static function setHeader(array $header = ['typ' => 'JWT', 'alg' => 'HS256']): string
    {
        return UrlEncode::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Create and encode JWT payload with expiration
     * 
     * @param array<string, mixed> $payload The payload data
     * @param int $expMinutes Expiration time in minutes
     * @return string Base64URL encoded payload
     */
    public static function setPayload(array $payload, int $expMinutes): string
    {
        $now = Carbon::now();
        $payload['iat'] = $now->timestamp;
        $payload['exp'] = $now->copy()->addMinutes($expMinutes)->timestamp;
        
        return UrlEncode::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
}