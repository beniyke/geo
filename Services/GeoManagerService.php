<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Core geo manager service.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services;

use Audit\Audit;
use Core\Services\ConfigServiceInterface;
use Geo\Models\Location;
use Geo\Services\Builders\DistanceBuilder;
use Geo\Services\Drivers\GoogleMapsDriver;
use Geo\Services\Drivers\NominatimDriver;
use Rollout\Rollout;

class GeoManagerService
{
    private ?object $driver = null;

    public function __construct(
        private readonly ConfigServiceInterface $config
    ) {
    }

    public function point(float $lat, float $lng): Location
    {
        return new Location($lat, $lng);
    }

    /**
     * Set the starting point for distance calculation.
     */
    public function from(float|Location|array $lat, ?float $lng = null): DistanceBuilder
    {
        return (new DistanceBuilder($this))->from($lat, $lng);
    }

    /**
     * Set the destination point for distance calculation.
     */
    public function to(float|Location|array $lat, ?float $lng = null): DistanceBuilder
    {
        return (new DistanceBuilder($this))->to($lat, $lng);
    }

    public function isActive(string $feature, Location $location): bool
    {
        if (class_exists(Rollout::class)) {
            return Rollout::isActive($feature, [
                'city' => $location->city,
                'country' => $location->countryCode,
                'location' => $location->toArray()
            ]);
        }

        return false;
    }

    public function geocode(string $address): ?Location
    {
        $location = $this->getDriver()->geocode($address);

        if ($location) {
            $this->logAudit('geo.geocoded', [
                'address' => $address,
                'result' => $location->toArray()
            ]);
        }

        return $location;
    }

    public function reverseGeocode(float $lat, float $lng): ?Location
    {
        $location = $this->getDriver()->reverseGeocode($lat, $lng);

        if ($location) {
            $this->logAudit('geo.reverse_geocoded', [
                'lat' => $lat,
                'lng' => $lng,
                'result' => $location->toArray()
            ]);
        }

        return $location;
    }

    public function distance(float $lat1, float $lng1, float $lat2, float $lng2, ?string $unit = null): float
    {
        $unit = $unit ?? $this->config->get('geo.distance_unit', 'km');
        $earthRadius = $unit === 'miles' ? 3959 : 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function locateIp(?string $ip = null): ?Location
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        // Use ip-api.com (free, no API key required)
        $url = "http://ip-api.com/json/{$ip}";
        $response = @file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (!$data || $data['status'] !== 'success') {
            return null;
        }

        $location = Location::fromArray([
            'latitude' => $data['lat'],
            'longitude' => $data['lon'],
            'city' => $data['city'] ?? null,
            'state' => $data['regionName'] ?? null,
            'country' => $data['country'] ?? null,
            'country_code' => $data['countryCode'] ?? null,
            'postal_code' => $data['zip'] ?? null,
            'raw' => $data,
        ]);

        $this->logAudit('geo.ip_located', [
            'ip' => $ip,
            'country' => $location->country,
            'country_code' => $location->countryCode
        ]);

        return $location;
    }

    public function withinRadius(
        float $lat,
        float $lng,
        float $radius,
        string $model,
        string $latColumn = 'latitude',
        string $lngColumn = 'longitude'
    ): array {
        $unit = $this->config->get('geo.distance_unit', 'km');
        $earthRadius = $unit === 'miles' ? 3959 : 6371;

        // Using Haversine formula in SQL
        $haversine = "({$earthRadius} * acos(
            cos(radians({$lat})) * 
            cos(radians({$latColumn})) * 
            cos(radians({$lngColumn}) - radians({$lng})) + 
            sin(radians({$lat})) * 
            sin(radians({$latColumn}))
        ))";

        return $model::selectRaw("*, {$haversine} AS distance")
            ->whereRaw("{$haversine} <= ?", [$radius])
            ->orderBy('distance')
            ->get()
            ->all();
    }

    private function getDriver(): object
    {
        if ($this->driver) {
            return $this->driver;
        }

        $driverName = $this->config->get('geo.driver', 'google');

        $this->driver = match ($driverName) {
            'google' => new GoogleMapsDriver($this->config),
            'nominatim' => new NominatimDriver($this->config),
            default => new GoogleMapsDriver($this->config),
        };

        return $this->driver;
    }

    /**
     * Helper to log audit events if the package exists.
     */
    private function logAudit(string $event, array $metadata): void
    {
        if (class_exists(Audit::class)) {
            Audit::make()
                ->event($event)
                ->metadata($metadata)
                ->log();
        }
    }
}
