<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Geo Analytics Service.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Geo\Services;

use Audit\Audit;
use Core\Services\ConfigServiceInterface;
use Helpers\DateTimeHelper;

class GeoAnalyticsService
{
    public function __construct(
        private readonly ConfigServiceInterface $config
    ) {
    }

    /**
     * Get the overall API usage and success metrics.
     */
    public function getOverview(): array
    {
        $events = ['geo.geocoded', 'geo.reverse_geocoded', 'geo.ip_located'];

        return [
            'total_requests' => Audit::countByEvent($events),
            'geocoding_requests' => Audit::countByEvent('geo.geocoded'),
            'reverse_geocoding_requests' => Audit::countByEvent('geo.reverse_geocoded'),
            'ip_lookups' => Audit::countByEvent('geo.ip_located'),
        ];
    }

    public function getDailyTrends(int $days = 30): array
    {
        $trends = [];
        $events = ['geo.geocoded', 'geo.reverse_geocoded', 'geo.ip_located'];
        $startDate = DateTimeHelper::now()->subDays($days);

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->addDays($i)->format('Y-m-d');
            $nextDate = $startDate->addDays($i + 1)->format('Y-m-d');

            $trends[] = [
                'date' => $date,
                'requests' => Audit::countByEvent($events, $date, $nextDate),
            ];
        }

        return $trends;
    }

    public function getRegionalDistribution(): array
    {
        $logs = Audit::query([
            'event' => 'geo.ip_located',
            'metadata_has' => true
        ]);

        $distribution = [];
        $total = $logs->count();

        if ($total === 0) {
            return [];
        }

        foreach ($logs as $log) {
            $country = $log->metadata['country'] ?? 'Unknown';
            $distribution[$country] = ($distribution[$country] ?? 0) + 1;
        }

        arsort($distribution);

        $result = [];
        foreach ($distribution as $country => $count) {
            $result[] = [
                'country' => $country,
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 2)
            ];
        }

        return $result;
    }
}
