<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ActivityTypeService
{
    /**
     * How many of the most recent sequences a progress chart covers.
     */
    protected const int CHART_LENGTH = 10;

    /**
     * Get every activity type the user has created, with their activity counts.
     *
     * @return Collection<int, ActivityType>
     */
    public function getUserActivityTypes(User $user): Collection
    {
        return $user->activityTypes()
            ->withCount('activities')
            ->orderBy('type')
            ->get();
    }

    /**
     * Resolve an activity type by name for the given user, creating it the
     * first time that name is used.
     */
    public function getActivityType(User $user, string $type): ActivityType
    {
        return $user->activityTypes()->firstOrCreate(['type' => $type]);
    }

    /**
     * Get the repetitions and weights of the most recent sequences recorded
     * for an activity type, oldest first.
     *
     * @return array{repetition: list<int>, weight: list<float>}
     */
    public function getValues(ActivityType $activityType): array
    {
        $sequences = Sequence::query()
            ->whereHas('activity', fn ($query) => $query->where('activity_type_id', $activityType->id))
            ->latest('id')
            ->limit(self::CHART_LENGTH)
            ->get()
            ->reverse();

        return [
            'repetition' => array_values($sequences->map(fn (Sequence $sequence): int => $sequence->repetition)->all()),
            'weight' => array_values($sequences->map(fn (Sequence $sequence): float => $sequence->weight ?? 0.0)->all()),
        ];
    }
}
