<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * OpenStreetMap Nominatim geocoding driver.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services\Drivers;

use Core\Services\ConfigServiceInterface;
use Geo\Models\Location;

class NominatimDriver
{
    private const SEARCH_URL = 'https://nominatim.openstreetmap.org/search';
    private const REVERSE_URL = 'https://nominatim.openstreetmap.org/reverse';

    public function __construct(
        private readonly ConfigServiceInterface $config
    ) {
    }

    public function geocode(string $address): ?Location
    {
        $params = [
            'q' => $address,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
        ];

        if ($language = $this->config->get('geo.drivers.nominatim.language')) {
            $params['accept-language'] = $language;
        }

        $url = self::SEARCH_URL . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: ' . $this->config->get('geo.drivers.nominatim.user_agent', 'Anchor'),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data)) {
            return null;
        }

        return $this->parseResult($data[0]);
    }

    /**
     * Reverse geocode coordinates.
     */
    public function reverseGeocode(float $lat, float $lng): ?Location
    {
        $params = [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
            'addressdetails' => 1,
        ];

        if ($language = $this->config->get('geo.drivers.nominatim.language')) {
            $params['accept-language'] = $language;
        }

        $url = self::REVERSE_URL . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: ' . $this->config->get('geo.drivers.nominatim.user_agent', 'Anchor'),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        if (empty($data) || isset($data['error'])) {
            return null;
        }

        return $this->parseResult($data);
    }

    /**
     * Parse a Nominatim result.
     */
    private function parseResult(array $result): Location
    {
        $address = $result['address'] ?? [];

        return Location::fromArray([
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'formatted_address' => $result['display_name'] ?? null,
            'street' => trim(($address['house_number'] ?? '') . ' ' . ($address['road'] ?? '')),
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            'state' => $address['state'] ?? null,
            'country' => $address['country'] ?? null,
            'country_code' => strtoupper($address['country_code'] ?? ''),
            'postal_code' => $address['postcode'] ?? null,
            'raw' => $result,
        ]);
    }
}
