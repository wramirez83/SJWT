<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tools;

/**
 * Utility class for Base64URL encoding/decoding
 * 
 * Base64URL is a URL-safe variant of Base64 encoding used in JWT tokens.
 * It replaces '+' with '-', '/' with '_', and removes padding '=' characters.
 */
class UrlEncode
{
    /**
     * Encode data using Base64URL encoding
     * 
     * @param string $data The data to encode
     * @return string Base64URL encoded string
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode Base64URL encoded data
     * 
     * @param string $data The Base64URL encoded string
     * @return string|false Decoded string or false on failure
     */
    public static function base64UrlDecode(string $data): string|false
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}