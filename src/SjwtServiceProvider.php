<?php

declare(strict_types=1);

namespace Wramirez83\Sjwt;

use Illuminate\Support\ServiceProvider;

/**
 * SJWT Service Provider for Laravel
 * 
 * Registers the SJWT package with Laravel and publishes configuration.
 */
class SjwtServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sjwt.php',
            'sjwt'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/sjwt.php' => config_path('sjwt.php'),
        ], 'sjwt-config');
    }
}

