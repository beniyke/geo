<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Google Maps geocoding driver.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services\Drivers;

use Core\Services\ConfigServiceInterface;
use Geo\Models\Location;
use RuntimeException;

class GoogleMapsDriver
{
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct(
        private readonly ConfigServiceInterface $config
    ) {
    }

    public function geocode(string $address): ?Location
    {
        $apiKey = $this->config->get('geo.drivers.google.api_key');

        if (!$apiKey) {
            throw new RuntimeException('Google Maps API key not configured.');
        }

        $params = [
            'address' => $address,
            'key' => $apiKey,
        ];

        if ($language = $this->config->get('geo.drivers.google.language')) {
            $params['language'] = $language;
        }

        if ($region = $this->config->get('geo.drivers.google.region')) {
            $params['region'] = $region;
        }

        $url = self::GEOCODE_URL . '?' . http_build_query($params);
        $response = @file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return null;
        }

        return $this->parseResult($data['results'][0]);
    }

    /**
     * Reverse geocode coordinates.
     */
    public function reverseGeocode(float $lat, float $lng): ?Location
    {
        $apiKey = $this->config->get('geo.drivers.google.api_key');

        if (!$apiKey) {
            throw new RuntimeException('Google Maps API key not configured.');
        }

        $params = [
            'latlng' => "{$lat},{$lng}",
            'key' => $apiKey,
        ];

        if ($language = $this->config->get('geo.drivers.google.language')) {
            $params['language'] = $language;
        }

        $url = self::GEOCODE_URL . '?' . http_build_query($params);
        $response = @file_get_contents($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return null;
        }

        return $this->parseResult($data['results'][0]);
    }

    /**
     * Parse a Google Maps result.
     */
    private function parseResult(array $result): Location
    {
        $components = [];

        foreach ($result['address_components'] ?? [] as $component) {
            foreach ($component['types'] as $type) {
                $components[$type] = $component['long_name'];
                $components[$type . '_short'] = $component['short_name'];
            }
        }

        return Location::fromArray([
            'latitude' => $result['geometry']['location']['lat'],
            'longitude' => $result['geometry']['location']['lng'],
            'formatted_address' => $result['formatted_address'] ?? null,
            'street' => trim(($components['street_number'] ?? '') . ' ' . ($components['route'] ?? '')),
            'city' => $components['locality'] ?? $components['sublocality'] ?? null,
            'state' => $components['administrative_area_level_1'] ?? null,
            'country' => $components['country'] ?? null,
            'country_code' => $components['country_short'] ?? null,
            'postal_code' => $components['postal_code'] ?? null,
            'raw' => $result,
        ]);
    }
}
