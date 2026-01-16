<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Trait for models with location data.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Traits;

use Geo\Geo;
use Geo\Models\Location;

trait HasLocation
{
    public function getLatitudeColumn(): string
    {
        return $this->latitudeColumn ?? 'latitude';
    }

    public function getLongitudeColumn(): string
    {
        return $this->longitudeColumn ?? 'longitude';
    }

    public function getLatitude(): ?float
    {
        $column = $this->getLatitudeColumn();

        return $this->$column ? (float) $this->$column : null;
    }

    public function getLongitude(): ?float
    {
        $column = $this->getLongitudeColumn();

        return $this->$column ? (float) $this->$column : null;
    }

    /**
     * Set coordinates from a Location object.
     */
    public function setLocationFrom(Location $location): self
    {
        $latColumn = $this->getLatitudeColumn();
        $lngColumn = $this->getLongitudeColumn();

        $this->$latColumn = $location->latitude;
        $this->$lngColumn = $location->longitude;

        return $this;
    }

    /**
     * Geocode an address and set coordinates.
     */
    public function geocodeAddress(string $address): bool
    {
        $location = Geo::geocode($address);

        if (!$location) {
            return false;
        }

        $this->setLocationFrom($location);

        return true;
    }

    public function distanceTo(mixed $target): ?float
    {
        $myLat = $this->getLatitude();
        $myLng = $this->getLongitude();

        if (!$myLat || !$myLng) {
            return null;
        }

        if (is_object($target) && method_exists($target, 'getLatitude')) {
            $targetLat = $target->getLatitude();
            $targetLng = $target->getLongitude();
        } elseif (is_array($target)) {
            $targetLat = $target['lat'] ?? $target['latitude'] ?? null;
            $targetLng = $target['lng'] ?? $target['longitude'] ?? null;
        } else {
            return null;
        }

        if (!$targetLat || !$targetLng) {
            return null;
        }

        return Geo::distance($myLat, $myLng, $targetLat, $targetLng);
    }

    public function hasCoordinates(): bool
    {
        return $this->getLatitude() !== null && $this->getLongitude() !== null;
    }

    public function scopeWithinRadius(float $lat, float $lng, float $radius): static
    {
        $latColumn = $this->getLatitudeColumn();
        $lngColumn = $this->getLongitudeColumn();
        $earthRadius = 6371; // km

        $haversine = "({$earthRadius} * acos(
            cos(radians({$lat})) * 
            cos(radians({$latColumn})) * 
            cos(radians({$lngColumn}) - radians({$lng})) + 
            sin(radians({$lat})) * 
            sin(radians({$latColumn}))
        ))";

        return $this->selectRaw("*, {$haversine} AS distance")
            ->whereRaw("{$haversine} <= ?", [$radius])
            ->orderBy('distance');
    }
}
