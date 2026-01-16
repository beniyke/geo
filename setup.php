<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Geo Package Setup
 *
 * Geolocation and mapping for the Anchor Framework.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

return [
    'providers' => [
        Geo\Providers\GeoServiceProvider::class,
    ],
    'middleware' => [],
];
