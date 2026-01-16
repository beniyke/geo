<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Service provider for the Geo package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Providers;

use Core\Services\ServiceProvider;
use Geo\Services\GeoManagerService;

class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(GeoManagerService::class);
    }

    public function boot(): void
    {
        // Any boot logic
    }
}
