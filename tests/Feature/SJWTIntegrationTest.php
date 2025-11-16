<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tests\Feature;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Wramirez83\Sjwt\SJWT;
use Wramirez83\Sjwt\UserAuth;

class SJWTIntegrationTest extends TestCase
{
    private string $testSecret = 'integration-test-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['SECRET_JWT'] = $this->testSecret;
        SJWT::clearSecretCache();
        UserAuth::reset();
    }

    protected function tearDown(): void
    {
        unset($_ENV['SECRET_JWT']);
        SJWT::clearSecretCache();
        UserAuth::reset();
        parent::tearDown();
    }

    public function testFullAuthenticationFlow(): void
    {
        // 1. Encode user data into JWT
        $userData = [
            'id' => 100,
            'email' => 'integration@example.com',
            'name' => 'Integration Test User',
            'role' => 'admin',
        ];
        
        $token = SJWT::encode($userData, 60);
        $this->assertNotEmpty($token);
        
        // 2. Decode and validate token
        $result = SJWT::decode($token);
        $this->assertTrue($result->valid);
        $this->assertTrue($result->signatureValid);
        $this->assertFalse($result->tokenExpired);
        
        // 3. Set user auth from payload
        UserAuth::user()->setAtt((array)$result->payload);
        
        // 4. Verify user auth data
        $this->assertEquals(100, UserAuth::user()->id());
        $this->assertEquals('integration@example.com', UserAuth::user()->email());
        $this->assertEquals('Integration Test User', UserAuth::user()->name());
        $this->assertEquals('admin', UserAuth::user()->get('role'));
    }

    public function testTokenExpirationFlow(): void
    {
        // Create token with very short expiration
        $payload = ['user_id' => 200];
        $token = SJWT::encode($payload, 0); // Expires immediately
        
        // Wait a moment
        usleep(100000); // 0.1 seconds
        
        $result = SJWT::decode($token);
        
        $this->assertTrue($result->tokenExpired);
        $this->assertFalse($result->valid);
        $this->assertTrue($result->signatureValid); // Signature is still valid
    }

    public function testMultipleTokensWithSameSecret(): void
    {
        $secret = $this->testSecret;
        
        $token1 = SJWT::encode(['user_id' => 1], 60);
        $token2 = SJWT::encode(['user_id' => 2], 60);
        
        $result1 = SJWT::decode($token1);
        $result2 = SJWT::decode($token2);
        
        $this->assertTrue($result1->valid);
        $this->assertTrue($result2->valid);
        $this->assertEquals(1, $result1->payload->user_id);
        $this->assertEquals(2, $result2->payload->user_id);
    }

    public function testTokenWithComplexPayload(): void
    {
        $complexPayload = [
            'user' => [
                'id' => 300,
                'email' => 'complex@example.com',
            ],
            'permissions' => ['read', 'write', 'delete'],
            'metadata' => [
                'created_at' => '2024-01-01',
                'last_login' => '2024-01-15',
            ],
        ];
        
        $token = SJWT::encode($complexPayload, 120);
        $result = SJWT::decode($token);
        
        $this->assertTrue($result->valid);
        $this->assertIsObject($result->payload->user);
        $this->assertIsArray($result->payload->permissions);
        $this->assertCount(3, $result->payload->permissions);
    }
}

