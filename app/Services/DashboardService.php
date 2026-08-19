<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Program;
use App\Models\ProgramItem;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class DashboardService
{
    /**
     * How many weeks back the volume chart reaches.
     */
    public const int CHART_WEEKS = 8;

    /**
     * How many records the dashboard lists.
     */
    protected const int RECORDS = 3;

    /**
     * How far back a personal record still counts as recent.
     */
    protected const int RECORD_DAYS = 30;

    /**
     * What a single set is worth when estimating how long a session runs. Sets
     * differ wildly, so this is a rough figure meant to set expectations, not
     * a promise.
     */
    protected const int MINUTES_PER_SET = 3;

    /**
     * The headline numbers shown at the top of the dashboard: how much the
     * user trained this month, how much they lifted, and where their weight
     * currently sits.
     *
     * @return array{
     *     trainings: int,
     *     sequences: int,
     *     volume: float,
     *     volume_change: int|null,
     *     weight: float|null,
     *     weight_change: float|null,
     * }
     */
    public function summary(User $user): array
    {
        $start = now()->startOfMonth();

        $trainings = $user->trainings()->where('date', '>=', $start)->count();

        $sequences = $this->sequences($user, $start);

        $volume = $this->volume($sequences);
        $previous = $this->volume($this->sequences($user, $start->clone()->subMonthNoOverflow(), $start));

        $metrics = $user->metrics()
            ->where('key', 'weight')
            ->latest('date')
            ->limit(2)
            ->pluck('value');

        $weight = $metrics->first() !== null ? (float) $metrics->first() : null;
        $previousWeight = $metrics->get(1) !== null ? (float) $metrics->get(1) : null;

        return [
            'trainings' => $trainings,
            'sequences' => $sequences->count(),
            'volume' => $volume,
            // A month with nothing to compare against has no trend to report.
            'volume_change' => $previous > 0.0
                ? (int) round(($volume - $previous) / $previous * 100)
                : null,
            'weight' => $weight,
            'weight_change' => $weight !== null && $previousWeight !== null
                ? round($weight - $previousWeight, 1)
                : null,
        ];
    }

    /**
     * The weekly series behind the dashboard chart: how much was lifted, and
     * how many sessions it took, oldest week first. Volume comes back in
     * tonnes, which is the unit the chart is labelled in.
     *
     * @return array{labels: list<string>, volume: list<float>, sessions: list<int>}
     */
    public function series(User $user, int $weeks = self::CHART_WEEKS): array
    {
        $first = now()->startOfWeek()->subWeeks($weeks - 1);

        $volumes = $this->volumeByWeek($user, $first);

        $sessions = $user->trainings()
            ->where('date', '>=', $first)
            ->get(['date'])
            ->countBy(fn (Training $training): string => $this->week($training->date));

        $labels = [];
        $volume = [];
        $counts = [];

        for ($week = $weeks - 1; $week >= 0; $week--) {
            $date = now()->startOfWeek()->subWeeks($week);

            $labels[] = __('W:week', ['week' => $date->isoWeek()]);
            $volume[] = round(($volumes[$this->week($date)] ?? 0.0) / 1000, 1);
            $counts[] = $sessions->get($this->week($date), 0);
        }

        return ['labels' => $labels, 'volume' => $volume, 'sessions' => $counts];
    }

    /**
     * The current week, day by day, with how it compares to the one before, so
     * the user can see at a glance what is left of it.
     *
     * @return array{days: list<array{letter: string, done: bool}>, done: int, previous: int}
     */
    public function currentWeek(User $user): array
    {
        $start = now()->startOfWeek();

        $dates = $user->trainings()
            ->whereBetween('date', [$start, $start->clone()->endOfWeek()])
            ->get(['date'])
            ->map(fn (Training $training): string => $training->date->toDateString());

        $days = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $start->clone()->addDays($day);

            $days[] = [
                // The initial of the weekday, in whichever language is on.
                'letter' => mb_strtoupper(mb_substr($date->translatedFormat('D'), 0, 1)),
                'done' => $dates->contains($date->toDateString()),
            ];
        }

        return [
            'days' => $days,
            'done' => $dates->count(),
            'previous' => $user->trainings()
                ->whereBetween('date', [$start->clone()->subWeek(), $start->clone()->subSecond()])
                ->count(),
        ];
    }

    /**
     * The heaviest set the user pulled off on each exercise lately, heaviest
     * first, one line per exercise.
     *
     * @return list<array{exercise: string, weight: float, repetition: int}>
     */
    public function records(User $user, int $limit = self::RECORDS): array
    {
        $records = $this->joinTrainings(Sequence::query())
            ->join('exercises', 'exercises.id', '=', 'activities.exercise_id')
            ->where('trainings.user_id', $user->id)
            ->where('trainings.date', '>=', now()->subDays(self::RECORD_DAYS))
            ->where('sequences.weight', '>', 0)
            ->orderByDesc('sequences.weight')
            ->get(['exercises.id as exercise_id', 'exercises.name as exercise_name', 'sequences.weight', 'sequences.repetition'])
            ->unique('exercise_id')
            ->take($limit)
            ->map(fn (Sequence $sequence): array => [
                'exercise' => (string) $sequence->getAttribute('exercise_name'),
                'weight' => (float) $sequence->weight,
                'repetition' => $sequence->repetition,
            ])
            ->all();

        return array_values($records);
    }

    /**
     * The session the "start" button launches: the program the user trained
     * from last, since that is the one they are working through. Null until
     * they have started a session from a program at least once.
     *
     * @return array{program: Program, exercises: int, minutes: int}|null
     */
    public function nextSession(User $user): ?array
    {
        $programId = Activity::query()
            ->join('trainings', 'trainings.id', '=', 'activities.training_id')
            ->join('program_items', 'program_items.id', '=', 'activities.program_item_id')
            ->where('trainings.user_id', $user->id)
            ->orderByDesc('trainings.date')
            ->orderByDesc('activities.id')
            ->value('program_items.program_id');

        if ($programId === null) {
            return null;
        }

        $program = Program::query()
            ->with('items.targets')
            ->whereKey($programId)
            ->first();

        if ($program === null || $program->items->isEmpty()) {
            return null;
        }

        $sets = $program->items->sum(
            fn (ProgramItem $item): int => (int) $item->targets->sum('sets'),
        );

        return [
            'program' => $program,
            'exercises' => $program->items->count(),
            'minutes' => max(self::MINUTES_PER_SET, $sets * self::MINUTES_PER_SET),
        ];
    }

    /**
     * The sets the user logged over a period, from the given date up to the
     * next one, or up to now.
     *
     * @return Collection<int, Sequence>
     */
    private function sequences(User $user, CarbonInterface $from, ?CarbonInterface $until = null): Collection
    {
        return Sequence::query()
            ->whereHas('activity.training', function (Builder $query) use ($user, $from, $until): void {
                $query->where('user_id', $user->id)->where('date', '>=', $from);

                if ($until !== null) {
                    $query->where('date', '<', $until);
                }
            })
            ->get(['repetition', 'weight']);
    }

    /**
     * The classic tonnage of a set of sequences: reps multiplied by load,
     * summed. A bodyweight set adds nothing.
     *
     * @param  Collection<int, Sequence>  $sequences
     */
    private function volume(Collection $sequences): float
    {
        return round((float) $sequences->sum(
            fn (Sequence $sequence): float => $sequence->repetition * ($sequence->weight ?? 0.0),
        ), 1);
    }

    /**
     * The tonnage the user lifted in each week since the given date, keyed by
     * week. One query, since the chart covers a couple of months of sets.
     *
     * @return array<string, float>
     */
    private function volumeByWeek(User $user, CarbonInterface $from): array
    {
        return $this->joinTrainings(Sequence::query())
            ->where('trainings.user_id', $user->id)
            ->where('trainings.date', '>=', $from)
            ->get(['trainings.date as training_date', 'sequences.repetition', 'sequences.weight'])
            ->groupBy(fn (Sequence $sequence): string => $this->week(Date::parse($sequence->getAttribute('training_date'))))
            ->map(fn (Collection $sequences): float => $this->volume($sequences))
            ->all();
    }

    /**
     * Walk a sequence query up to the training it belongs to.
     *
     * @param  Builder<Sequence>  $query
     * @return Builder<Sequence>
     */
    private function joinTrainings(Builder $query): Builder
    {
        return $query
            ->join('activities', 'activities.id', '=', 'sequences.activity_id')
            ->join('trainings', 'trainings.id', '=', 'activities.training_id');
    }

    /**
     * The key a date is grouped under, as in "2026-34". ISO weeks, so a week
     * spanning two years still lands in one bucket.
     */
    private function week(CarbonInterface $date): string
    {
        return $date->format('o-W');
    }
}
