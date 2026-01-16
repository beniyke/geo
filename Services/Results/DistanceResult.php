<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Distance result object for fluent comparisons.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services\Results;

class DistanceResult
{
    public function __construct(
        private readonly float $value,
        private readonly string $unit
    ) {
    }

    public function value(): float
    {
        return $this->value;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    /**
     * Fluent comparison: greater than.
     */
    public function isGreaterThan(float $threshold): bool
    {
        return $this->value > $threshold;
    }

    /**
     * Fluent comparison: less than.
     */
    public function isLessThan(float $threshold): bool
    {
        return $this->value < $threshold;
    }

    /**
     * Fluent comparison: within a range.
     */
    public function isWithin(float $min, float $max): bool
    {
        return $this->value >= $min && $this->value <= $max;
    }

    /**
     * Convert to string (value + unit).
     */
    public function __toString(): string
    {
        return $this->value . ' ' . $this->unit;
    }
}
