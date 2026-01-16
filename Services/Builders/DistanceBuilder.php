<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Fluent distance builder for Geo package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services\Builders;

use Geo\Models\Location;
use Geo\Services\GeoManagerService;
use Geo\Services\Results\DistanceResult;

class DistanceBuilder
{
    private float $lat1;

    private float $lng1;

    private float $lat2;

    private float $lng2;

    private ?string $unit = null;

    public function __construct(
        private readonly GeoManagerService $manager
    ) {
    }

    /**
     * Set the starting point.
     */
    public function from(float|Location|array $lat, ?float $lng = null): self
    {
        if ($lat instanceof Location) {
            $this->lat1 = $lat->latitude;
            $this->lng1 = $lat->longitude;
        } elseif (is_array($lat)) {
            $this->lat1 = (float) ($lat['latitude'] ?? $lat['lat'] ?? 0);
            $this->lng1 = (float) ($lat['longitude'] ?? $lat['lng'] ?? 0);
        } else {
            $this->lat1 = (float) $lat;
            $this->lng1 = (float) $lng;
        }

        return $this;
    }

    /**
     * Set the destination point.
     */
    public function to(float|Location|array $lat, ?float $lng = null): self
    {
        if ($lat instanceof Location) {
            $this->lat2 = $lat->latitude;
            $this->lng2 = $lat->longitude;
        } elseif (is_array($lat)) {
            $this->lat2 = (float) ($lat['latitude'] ?? $lat['lat'] ?? 0);
            $this->lng2 = (float) ($lat['longitude'] ?? $lat['lng'] ?? 0);
        } else {
            $this->lat2 = (float) $lat;
            $this->lng2 = (float) $lng;
        }

        return $this;
    }

    /**
     * Set the distance unit (km or miles).
     */
    public function unit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Calculate and return the distance result.
     */
    public function get(): DistanceResult
    {
        $value = $this->manager->distance(
            $this->lat1,
            $this->lng1,
            $this->lat2,
            $this->lng2,
            $this->unit
        );

        return new DistanceResult($value, $this->unit ?? 'km');
    }
}
