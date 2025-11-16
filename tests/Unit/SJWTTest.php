<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Wramirez83\Sjwt\SJWT;

class SJWTTest extends TestCase
{
    private string $testSecret = 'test-secret-key-for-unit-tests-only';

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['SECRET_JWT'] = $this->testSecret;
        SJWT::clearSecretCache();
    }

    protected function tearDown(): void
    {
        unset($_ENV['SECRET_JWT']);
        SJWT::clearSecretCache();
        parent::tearDown();
    }

    public function testEncode(): void
    {
        $payload = ['user_id' => 123, 'email' => 'test@example.com'];
        $token = SJWT::encode($payload, 60);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // JWT should have 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testEncodeThrowsExceptionWhenSecretNotSet(): void
    {
        unset($_ENV['SECRET_JWT']);
        SJWT::clearSecretCache();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JWT secret is not set');
        
        SJWT::encode(['test' => 'data']);
    }

    public function testDecodeValidToken(): void
    {
        $payload = ['user_id' => 123, 'email' => 'test@example.com'];
        $token = SJWT::encode($payload, 60);
        
        $result = SJWT::decode($token);
        
        $this->assertIsObject($result);
        $this->assertFalse(isset($result->error));
        $this->assertTrue($result->signatureValid);
        $this->assertFalse($result->tokenExpired);
        $this->assertTrue($result->valid);
        $this->assertEquals(123, $result->payload->user_id);
        $this->assertEquals('test@example.com', $result->payload->email);
    }

    public function testDecodeExpiredToken(): void
    {
        $payload = ['user_id' => 123];
        $token = SJWT::encode($payload, -1); // Expired immediately
        
        // Wait a moment to ensure expiration
        usleep(100000); // 0.1 seconds
        
        $result = SJWT::decode($token);
        
        $this->assertTrue($result->tokenExpired);
        $this->assertFalse($result->valid);
    }

    public function testDecodeInvalidTokenFormat(): void
    {
        $invalidToken = 'invalid.token';
        
        $result = SJWT::decode($invalidToken);
        
        $this->assertTrue(isset($result->error));
        $this->assertStringContainsString('Invalid JWT format', $result->message);
    }

    public function testDecodeInvalidSignature(): void
    {
        $payload = ['user_id' => 123];
        $token = SJWT::encode($payload, 60);
        
        // Modify the signature
        $parts = explode('.', $token);
        $parts[2] = 'invalid_signature';
        $invalidToken = implode('.', $parts);
        
        $result = SJWT::decode($invalidToken);
        
        $this->assertFalse($result->signatureValid);
        $this->assertFalse($result->valid);
    }

    public function testDecodeWithDifferentSecret(): void
    {
        $payload = ['user_id' => 123];
        $token = SJWT::encode($payload, 60);
        
        // Change secret
        $_ENV['SECRET_JWT'] = 'different-secret';
        SJWT::clearSecretCache();
        
        $result = SJWT::decode($token);
        
        $this->assertFalse($result->signatureValid);
        $this->assertFalse($result->valid);
    }

    public function testDecodeTokenWithoutExpiration(): void
    {
        // Create a token manually without expiration
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode(['user_id' => 123]));
        $signature = hash_hmac('sha256', "$header.$payload", $this->testSecret, true);
        $signatureEncoded = base64_encode($signature);
        
        $token = "$header.$payload.$signatureEncoded";
        
        $result = SJWT::decode($token);
        
        $this->assertTrue(isset($result->error));
        $this->assertStringContainsString('exp', $result->message);
    }

    public function testEncodeDecodeRoundTrip(): void
    {
        $originalPayload = [
            'user_id' => 456,
            'email' => 'roundtrip@example.com',
            'name' => 'Test User',
            'roles' => ['admin', 'user'],
        ];
        
        $token = SJWT::encode($originalPayload, 120);
        $result = SJWT::decode($token);
        
        $this->assertTrue($result->valid);
        $this->assertEquals($originalPayload['user_id'], $result->payload->user_id);
        $this->assertEquals($originalPayload['email'], $result->payload->email);
        $this->assertEquals($originalPayload['name'], $result->payload->name);
    }

    public function testClearSecretCache(): void
    {
        // Encode should cache the secret
        SJWT::encode(['test' => 'data']);
        
        // Clear cache
        SJWT::clearSecretCache();
        
        // Should still work (will re-fetch from env)
        $token = SJWT::encode(['test' => 'data2']);
        $this->assertNotEmpty($token);
    }
}

