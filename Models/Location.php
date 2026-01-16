<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Location model/DTO for geo results.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Models;

class Location
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $address = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $country = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $formattedAddress = null,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) ($data['latitude'] ?? $data['lat'] ?? 0),
            longitude: (float) ($data['longitude'] ?? $data['lng'] ?? 0),
            address: $data['address'] ?? null,
            street: $data['street'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            countryCode: $data['country_code'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            formattedAddress: $data['formatted_address'] ?? null,
            raw: $data['raw'] ?? []
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'postal_code' => $this->postalCode,
            'formatted_address' => $this->formattedAddress,
        ];
    }

    public function getCoordinates(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
