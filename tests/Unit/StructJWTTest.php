<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Wramirez83\Sjwt\Tools\StructJWT;
use Wramirez83\Sjwt\Tools\UrlEncode;

class StructJWTTest extends TestCase
{
    public function testSetHeader(): void
    {
        $header = StructJWT::setHeader();
        
        $this->assertIsString($header);
        $this->assertNotEmpty($header);
        
        // Decode and verify structure
        $decoded = UrlEncode::base64UrlDecode($header);
        $this->assertNotFalse($decoded);
        
        $headerData = json_decode($decoded, true);
        $this->assertEquals('JWT', $headerData['typ']);
        $this->assertEquals('HS256', $headerData['alg']);
    }

    public function testSetHeaderWithCustomHeader(): void
    {
        $customHeader = ['typ' => 'JWT', 'alg' => 'HS512'];
        $header = StructJWT::setHeader($customHeader);
        
        $decoded = UrlEncode::base64UrlDecode($header);
        $headerData = json_decode($decoded, true);
        
        $this->assertEquals('HS512', $headerData['alg']);
    }

    public function testSetPayload(): void
    {
        $payload = ['user_id' => 123, 'email' => 'test@example.com'];
        $expMinutes = 60;
        
        $encoded = StructJWT::setPayload($payload, $expMinutes);
        
        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);
        
        // Decode and verify
        $decoded = UrlEncode::base64UrlDecode($encoded);
        $this->assertNotFalse($decoded);
        
        $payloadData = json_decode($decoded, true);
        $this->assertEquals(123, $payloadData['user_id']);
        $this->assertEquals('test@example.com', $payloadData['email']);
        $this->assertArrayHasKey('iat', $payloadData);
        $this->assertArrayHasKey('exp', $payloadData);
        
        // Verify expiration is in the future
        $now = Carbon::now()->timestamp;
        $this->assertGreaterThan($now, $payloadData['exp']);
    }

    public function testSetPayloadExpiration(): void
    {
        $payload = ['test' => 'data'];
        $expMinutes = 30;
        
        $encoded = StructJWT::setPayload($payload, $expMinutes);
        $decoded = UrlEncode::base64UrlDecode($encoded);
        $payloadData = json_decode($decoded, true);
        
        $expectedExp = Carbon::now()->addMinutes($expMinutes)->timestamp;
        $actualExp = $payloadData['exp'];
        
        // Allow 5 seconds difference for execution time
        $this->assertLessThanOrEqual(5, abs($expectedExp - $actualExp));
    }
}

