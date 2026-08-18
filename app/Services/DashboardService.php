<?php

namespace App\Services;

use App\Models\Sequence;
use App\Models\User;

class DashboardService
{
    /**
     * The headline numbers shown at the top of the dashboard: how much the
     * user trained this month, how much they lifted, and where their weight
     * currently sits.
     *
     * @return array{
     *     trainings: int,
     *     sequences: int,
     *     volume: float,
     *     weight: float|null,
     *     weight_change: float|null,
     * }
     */
    public function summary(User $user): array
    {
        $start = now()->startOfMonth();

        $trainings = $user->trainings()->where('date', '>=', $start)->count();

        $sequences = Sequence::query()
            ->whereHas(
                'activity.training',
                fn ($query) => $query->where('user_id', $user->id)->where('date', '>=', $start),
            )
            ->get(['repetition', 'weight']);

        $metrics = $user->metrics()
            ->where('key', 'weight')
            ->latest('date')
            ->limit(2)
            ->pluck('value');

        $weight = $metrics->first() !== null ? (float) $metrics->first() : null;
        $previous = $metrics->get(1) !== null ? (float) $metrics->get(1) : null;

        return [
            'trainings' => $trainings,
            'sequences' => $sequences->count(),
            // Volume is the classic tonnage: reps multiplied by load, summed.
            'volume' => round((float) $sequences->sum(
                fn (Sequence $sequence): float => $sequence->repetition * ($sequence->weight ?? 0.0),
            ), 1),
            'weight' => $weight,
            'weight_change' => $weight !== null && $previous !== null
                ? round($weight - $previous, 1)
                : null,
        ];
    }
}
