<?php

namespace App\Services;

use App\Models\Metric;
use App\Models\User;

class MetricService
{
    /**
     * How many of the most recent measurements a chart covers.
     */
    protected const int CHART_LENGTH = 20;

    /**
     * Get the user's most recent weight measurements as a chart series,
     * oldest first.
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    public function getSeries(User $user): array
    {
        $metrics = $user->metrics()
            ->where('key', 'weight')
            ->latest('date')
            ->limit(self::CHART_LENGTH)
            ->get()
            ->reverse();

        return [
            'labels' => $metrics->map(fn (Metric $metric): string => $metric->date->format('d.m'))->values()->all(),
            'values' => $metrics->map(fn (Metric $metric): float => (float) $metric->value)->values()->all(),
        ];
    }
}
