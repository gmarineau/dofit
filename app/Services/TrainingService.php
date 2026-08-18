<?php

namespace App\Services;

use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrainingService
{
    /**
     * How many months back the trainings chart reaches.
     */
    protected const int CHART_MONTHS = 6;

    /**
     * Close the session: everything still open counts as done, so the user
     * never has to tick off the last exercises one by one.
     */
    public function complete(Training $training): void
    {
        DB::transaction(function () use ($training): void {
            $training->activities()
                ->whereNull('completed_at')
                ->update(['completed_at' => now()]);

            $training->update(['completed_at' => now()]);
        });
    }

    /**
     * Keep the session in step with its activities: it closes itself as soon as
     * nothing is left to tick off, and reopens the moment something is. An
     * empty session is left alone, since there is nothing to have finished.
     */
    public function syncCompletion(Training $training): void
    {
        if ($training->activities()->count() === 0) {
            return;
        }

        $open = $training->activities()->whereNull('completed_at')->exists();

        if ($open && $training->isCompleted()) {
            $this->reopen($training);
        }

        if (! $open && ! $training->isCompleted()) {
            $training->update(['completed_at' => now()]);
        }
    }

    /**
     * Reopen the session, leaving the activities as they were ticked off, so a
     * misplaced tap can be undone without losing the detail.
     */
    public function reopen(Training $training): void
    {
        $training->update(['completed_at' => null]);
    }

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
