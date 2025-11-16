<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt;

use Carbon\Carbon;
use Throwable;
use Wramirez83\Sjwt\Tools\StructJWT;
use Wramirez83\Sjwt\Tools\UrlEncode;

/**
 * Simple JWT (JSON Web Token) Library
 * 
 * A lightweight and efficient JWT implementation for Laravel applications.
 * Supports token encoding, decoding, and validation with configurable expiration.
 * 
 * @package Wramirez83\Sjwt
 */
class SJWT
{
    /**
     * Cache for the JWT secret to avoid repeated lookups
     * 
     * @var string|null
     */
    private static ?string $secretCache = null;

    /**
     * Get the JWT secret from configuration or environment
     * 
     * @return string
     * @throws \RuntimeException If secret is not configured
     */
    private static function getSecret(): string
    {
        if (self::$secretCache !== null) {
            return self::$secretCache;
        }

        // Try Laravel config first (for better integration)
        if (function_exists('config')) {
            $secret = config('sjwt.secret');
            if ($secret) {
                self::$secretCache = $secret;
                return $secret;
            }
        }

        // Fallback to environment variables
        $secret = $_ENV['SECRET_JWT'] ?? getenv('SECRET_JWT');
        
        if (empty($secret)) {
            throw new \RuntimeException(
                'JWT secret is not set. Please set SECRET_JWT in your .env file or config/sjwt.php'
            );
        }

        self::$secretCache = $secret;
        return $secret;
    }

    /**
     * Encode a payload into a JWT token
     * 
     * @param array<string, mixed> $payload The data to encode in the token
     * @param int $expMinutes Expiration time in minutes (default: 60)
     * @return string The encoded JWT token
     * @throws \RuntimeException If secret is not configured
     * 
     * @example
     * ```php
     * $token = SJWT::encode(['user_id' => 123, 'email' => 'user@example.com'], 120);
     * ```
     */
    public static function encode(array $payload, int $exp = 60): string
    {
        $secret = self::getSecret();

        $header = StructJWT::setHeader();
        $payloadEncoded = StructJWT::setPayload($payload, $exp);
        
        // Create signature
        $signatureInput = "$header.$payloadEncoded";
        $signature = hash_hmac('sha256', $signatureInput, $secret, true);
        $base64UrlSignature = UrlEncode::base64UrlEncode($signature);

        return "$header.$payloadEncoded.$base64UrlSignature";
    }

    /**
     * Decode and validate a JWT token
     * 
     * @param string $jwt The JWT token string. If '.', will attempt to read from request headers
     * @param string $headerName The HTTP header name to read from (default: 'Authorization')
     * @param int $tokenIndex Index of token in header if space-separated (default: 1, e.g., "Bearer {token}")
     * @return object Decoded token with validation results
     * 
     * @example
     * ```php
     * $result = SJWT::decode($token);
     * if ($result->signatureValid && !$result->tokenExpired) {
     *     $userId = $result->payload->user_id;
     * }
     * ```
     */
    public static function decode(
        string $jwt = '.',
        string $headerName = 'Authorization',
        int $tokenIndex = 1
    ): object {
        try {
            $secret = self::getSecret();

            // Extract token from request if needed
            if ($jwt === '.') {
                $jwt = self::extractTokenFromRequest($headerName, $tokenIndex);
            }

            // Validate JWT format
            $tokenParts = explode('.', $jwt);
            if (count($tokenParts) !== 3) {
                throw new \InvalidArgumentException('Invalid JWT format. Expected 3 parts separated by dots.');
            }

            [$headerEncoded, $payloadEncoded, $signatureProvided] = $tokenParts;

            // Decode header and payload
            $headerJson = UrlEncode::base64UrlDecode($headerEncoded);
            $payloadJson = UrlEncode::base64UrlDecode($payloadEncoded);

            if ($headerJson === false || $payloadJson === false) {
                throw new \RuntimeException('Invalid base64 encoding in JWT.');
            }

            // Parse JSON
            $header = json_decode($headerJson, false, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($payloadJson, false, 512, JSON_THROW_ON_ERROR);

            // Validate expiration exists
            if (!isset($payload->exp)) {
                throw new \UnexpectedValueException('Missing "exp" (expiration) claim in payload.');
            }

            // Check expiration
            $expiration = Carbon::createFromTimestamp((int)$payload->exp);
            $now = Carbon::now();
            $tokenExpired = $now->greaterThan($expiration);

            // Verify signature
            $signatureInput = "$headerEncoded.$payloadEncoded";
            $expectedSignature = hash_hmac('sha256', $signatureInput, $secret, true);
            $expectedBase64Signature = UrlEncode::base64UrlEncode($expectedSignature);
            $signatureValid = hash_equals($expectedBase64Signature, $signatureProvided);

            return (object)[
                'header' => $header,
                'payload' => $payload,
                'signature' => $signatureProvided,
                'signatureValid' => $signatureValid,
                'tokenExpired' => $tokenExpired,
                'valid' => $signatureValid && !$tokenExpired,
            ];
        } catch (Throwable $e) {
            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
                'signatureValid' => false,
                'tokenExpired' => false,
                'valid' => false,
            ];
        }
    }

    /**
     * Extract JWT token from HTTP request
     * 
     * @param string $headerName The header name
     * @param int $tokenIndex Index in space-separated header value
     * @return string The extracted token
     * @throws \RuntimeException If token cannot be extracted
     */
    private static function extractTokenFromRequest(string $headerName, int $tokenIndex): string
    {
        // Try Laravel Request facade first
        if (class_exists('\Illuminate\Support\Facades\Request')) {
            $header = \Illuminate\Support\Facades\Request::header($headerName);
            if ($header) {
                $parts = explode(' ', $header);
                if (isset($parts[$tokenIndex])) {
                    return $parts[$tokenIndex];
                }
            }
        }

        // Fallback to native PHP methods
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        // Handle case-insensitive header lookup
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $headerName) === 0) {
                $parts = explode(' ', (string)$value);
                if (isset($parts[$tokenIndex])) {
                    return $parts[$tokenIndex];
                }
            }
        }

        // Try $_SERVER as last resort
        $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        if (isset($_SERVER[$serverKey])) {
            $parts = explode(' ', $_SERVER[$serverKey]);
            if (isset($parts[$tokenIndex])) {
                return $parts[$tokenIndex];
            }
        }

        throw new \RuntimeException("JWT token not found in header '$headerName'.");
    }

    /**
     * Clear the secret cache (useful for testing)
     * 
     * @return void
     */
    public static function clearSecretCache(): void
    {
        self::$secretCache = null;
    }
}
