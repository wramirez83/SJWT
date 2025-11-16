<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt;

/**
 * User Authentication Singleton
 * 
 * Provides a singleton pattern for accessing authenticated user data
 * throughout the application after JWT validation.
 * 
 * @package Wramirez83\Sjwt
 */
final class UserAuth
{
    /**
     * Singleton instance
     * 
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * User attributes storage
     * 
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {}

    /**
     * Get the singleton instance
     * 
     * @return self
     */
    public static function user(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set user attributes
     * 
     * @param array<string, mixed> $data User data array
     * @return void
     */
    public function setAtt(array $data): void
    {
        $this->attributes = array_merge($this->attributes, $data);
    }

    /**
     * Get all user attributes
     * 
     * @return array<string, mixed>
     */
    public function getAtt(): array
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute
     * 
     * @param string $key Attribute key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Check if an attribute exists
     * 
     * @param string $key Attribute key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Get user ID (convenience method)
     * 
     * @return int|string|null
     */
    public function id(): int|string|null
    {
        return $this->get('id');
    }

    /**
     * Get user email (convenience method)
     * 
     * @return string|null
     */
    public function email(): ?string
    {
        return $this->get('email');
    }

    /**
     * Get user name (convenience method)
     * 
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->get('name');
    }

    /**
     * Refresh user data from database (if User model is available)
     * 
     * @return bool True if refresh was successful
     */
    public function refresh(): bool
    {
        $id = $this->id();
        
        if (!$id || !class_exists('\App\Models\User')) {
            return false;
        }

        $user = \App\Models\User::find($id);

        if ($user) {
            $this->setAtt($user->toArray());
            return true;
        }

        return false;
    }

    /**
     * Clear all user data
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->attributes = [];
    }

    /**
     * Reset the singleton instance (useful for testing)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

