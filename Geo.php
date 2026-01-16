<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Static facade for geo operations.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo;

use Geo\Models\Location;
use Geo\Services\Builders\DistanceBuilder;
use Geo\Services\GeoAnalyticsService;
use Geo\Services\GeoManagerService;

class Geo
{
    /**
     * Geocode an address to coordinates.
     */
    public static function geocode(string $address): ?Location
    {
        return resolve(GeoManagerService::class)->geocode($address);
    }

    /**
     * Reverse geocode coordinates to address.
     */
    public static function reverseGeocode(float $lat, float $lng): ?Location
    {
        return resolve(GeoManagerService::class)->reverseGeocode($lat, $lng);
    }

    /**
     * Calculate distance between two points.
     */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2, ?string $unit = null): float
    {
        return resolve(GeoManagerService::class)->distance($lat1, $lng1, $lat2, $lng2, $unit);
    }

    /**
     * Start a fluent distance calculation from a point.
     */
    public static function from(float|Location|array $lat, ?float $lng = null): DistanceBuilder
    {
        return resolve(GeoManagerService::class)->from($lat, $lng);
    }

    /**
     * Start a fluent distance calculation to a point.
     */
    public static function to(float|Location|array $lat, ?float $lng = null): DistanceBuilder
    {
        return resolve(GeoManagerService::class)->to($lat, $lng);
    }

    public static function locateIp(?string $ip = null): ?Location
    {
        return resolve(GeoManagerService::class)->locateIp($ip);
    }

    public static function withinRadius(float $lat, float $lng, float $radius, string $model, string $latColumn = 'latitude', string $lngColumn = 'longitude'): array
    {
        return resolve(GeoManagerService::class)->withinRadius($lat, $lng, $radius, $model, $latColumn, $lngColumn);
    }

    public static function isActive(string $feature, Location $location): bool
    {
        return resolve(GeoManagerService::class)->isActive($feature, $location);
    }

    public static function analytics(): GeoAnalyticsService
    {
        return resolve(GeoAnalyticsService::class);
    }

    /**
     * Forward static calls to GeoManagerService.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return resolve(GeoManagerService::class)->$method(...$arguments);
    }
}
