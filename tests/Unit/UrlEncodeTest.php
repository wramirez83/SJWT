<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wramirez83\Sjwt\Tools\UrlEncode;

class UrlEncodeTest extends TestCase
{
    public function testBase64UrlEncode(): void
    {
        $data = 'Hello World!';
        $encoded = UrlEncode::base64UrlEncode($data);
        
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testBase64UrlDecode(): void
    {
        $original = 'Hello World!';
        $encoded = UrlEncode::base64UrlEncode($original);
        $decoded = UrlEncode::base64UrlDecode($encoded);
        
        $this->assertEquals($original, $decoded);
    }

    public function testBase64UrlEncodeDecodeRoundTrip(): void
    {
        $testCases = [
            'Simple text',
            'Text with special chars: !@#$%^&*()',
            'Unicode: 你好世界',
            'JSON: {"user_id": 123, "email": "test@example.com"}',
            '',
        ];

        foreach ($testCases as $original) {
            $encoded = UrlEncode::base64UrlEncode($original);
            $decoded = UrlEncode::base64UrlDecode($encoded);
            $this->assertEquals($original, $decoded, "Failed for: $original");
        }
    }

    public function testBase64UrlDecodeInvalidInput(): void
    {
        $invalid = 'Invalid!@#$%^&*()';
        $result = UrlEncode::base64UrlDecode($invalid);
        
        // Should return false for invalid base64
        $this->assertFalse($result);
    }

    public function testBase64UrlEncodeWithPadding(): void
    {
        // Test that padding is correctly removed
        $data = 'test';
        $encoded = UrlEncode::base64UrlEncode($data);
        
        $this->assertStringEndsNotWith('=', $encoded);
    }
}

