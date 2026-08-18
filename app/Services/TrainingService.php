<?php

namespace App\Services;

use App\Models\User;

class TrainingService
{
    /**
     * How many months back the trainings chart reaches.
     */
    protected const int CHART_MONTHS = 6;

    /**
     * Get the number of trainings the user recorded in each of the last months,
     * oldest first.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public function getSeries(User $user): array
    {
        $start = now()->subMonths(self::CHART_MONTHS - 1)->startOfMonth();

        $counts = $user->trainings()
            ->where('date', '>=', $start)
            ->get(['date'])
            ->countBy(fn ($training) => $training->date->format('Y-m'));

        $labels = [];
        $values = [];

        for ($month = self::CHART_MONTHS - 1; $month >= 0; $month--) {
            $date = now()->subMonths($month)->startOfMonth();

            $labels[] = $date->format('m.Y');
            $values[] = $counts->get($date->format('Y-m'), 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
