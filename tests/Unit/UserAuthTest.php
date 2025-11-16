<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wramirez83\Sjwt\UserAuth;

class UserAuthTest extends TestCase
{
    protected function tearDown(): void
    {
        UserAuth::reset();
        parent::tearDown();
    }

    public function testSingletonPattern(): void
    {
        $instance1 = UserAuth::user();
        $instance2 = UserAuth::user();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testSetAtt(): void
    {
        $user = UserAuth::user();
        $data = ['id' => 123, 'email' => 'test@example.com', 'name' => 'Test User'];
        
        $user->setAtt($data);
        
        $this->assertEquals(123, $user->id());
        $this->assertEquals('test@example.com', $user->email());
        $this->assertEquals('Test User', $user->name());
    }

    public function testGetAtt(): void
    {
        $user = UserAuth::user();
        $data = ['id' => 456, 'email' => 'get@example.com'];
        
        $user->setAtt($data);
        $attributes = $user->getAtt();
        
        $this->assertIsArray($attributes);
        $this->assertEquals(456, $attributes['id']);
        $this->assertEquals('get@example.com', $attributes['email']);
    }

    public function testGet(): void
    {
        $user = UserAuth::user();
        $user->setAtt(['custom_field' => 'custom_value']);
        
        $this->assertEquals('custom_value', $user->get('custom_field'));
        $this->assertNull($user->get('non_existent'));
        $this->assertEquals('default', $user->get('non_existent', 'default'));
    }

    public function testHas(): void
    {
        $user = UserAuth::user();
        $user->setAtt(['has_field' => 'value']);
        
        $this->assertTrue($user->has('has_field'));
        $this->assertFalse($user->has('non_existent'));
    }

    public function testConvenienceMethods(): void
    {
        $user = UserAuth::user();
        $user->setAtt([
            'id' => 789,
            'email' => 'convenience@example.com',
            'name' => 'Convenience User',
        ]);
        
        $this->assertEquals(789, $user->id());
        $this->assertEquals('convenience@example.com', $user->email());
        $this->assertEquals('Convenience User', $user->name());
    }

    public function testClear(): void
    {
        $user = UserAuth::user();
        $user->setAtt(['id' => 999, 'email' => 'clear@example.com']);
        
        $this->assertEquals(999, $user->id());
        
        $user->clear();
        
        $this->assertNull($user->id());
        $this->assertEmpty($user->getAtt());
    }

    public function testReset(): void
    {
        $instance1 = UserAuth::user();
        $instance1->setAtt(['id' => 111]);
        
        UserAuth::reset();
        
        $instance2 = UserAuth::user();
        $this->assertNotSame($instance1, $instance2);
        $this->assertNull($instance2->id());
    }

    public function testMergeAttributes(): void
    {
        $user = UserAuth::user();
        $user->setAtt(['id' => 1, 'email' => 'first@example.com']);
        $user->setAtt(['name' => 'Updated Name']);
        
        $this->assertEquals(1, $user->id());
        $this->assertEquals('first@example.com', $user->email());
        $this->assertEquals('Updated Name', $user->name());
    }
}

