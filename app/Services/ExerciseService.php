<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Throwable;

class ExerciseService
{
    /**
     * How many engine hits to take before the list is narrowed further by the
     * muscle, equipment and favourite filters.
     */
    protected const int HITS = 200;

    /**
     * How many of the most recent sequences a progress chart covers.
     */
    protected const int CHART_LENGTH = 10;

    /**
     * Narrow the library by whatever the user typed. The search engine handles
     * typos and the French vocabulary through its synonyms; a plain name match
     * takes over when the engine cannot be reached, so a search never takes
     * the page down with it.
     *
     * @param  Builder<Exercise>  $query
     * @return Builder<Exercise>
     */
    public function applyTerm(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $hits = $this->hits($term);

        if ($hits === null) {
            return $query->matchingName($term);
        }

        return $this->inHitOrder($query->whereKey($hits), $hits);
    }

    /**
     * The exercises this user has already logged, most used first, so the ones
     * they actually train are offered before the whole library.
     *
     * @return Collection<int, Exercise>
     */
    public function logged(User $user): Collection
    {
        return Exercise::query()
            ->loggedBy($user)
            ->withCount(['activities as activities_count' => fn (Builder $query) => $query->whereHas(
                'training',
                fn (Builder $query) => $query->where('user_id', $user->id),
            )])
            ->orderByDesc('activities_count')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve what the picker handed over into an exercise: an entry from the
     * library, or one of the user's own, created the first time that name is
     * used.
     */
    public function resolve(User $user, ?int $exerciseId, string $name): Exercise
    {
        if ($exerciseId !== null) {
            return Exercise::query()->availableTo($user)->findOrFail($exerciseId);
        }

        $name = trim($name);

        $existing = Exercise::query()
            ->availableTo($user)
            ->where('name', $name)
            // Their own entry wins over a library one of the same name.
            ->orderByRaw('user_id is null')
            ->first();

        return $existing ?? $this->createCustom($user, ['name' => $name]);
    }

    /**
     * Add an exercise the library does not carry.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createCustom(User $user, array $attributes): Exercise
    {
        return $user->exercises()->create([
            'slug' => $this->uniqueSlug($attributes['name']),
            'primary_muscles' => [],
            'secondary_muscles' => [],
            'instructions' => [],
            ...$attributes,
        ]);
    }

    /**
     * Get the repetitions and weights of the user's most recent sequences for
     * an exercise, oldest first. Library exercises are shared, so this only
     * ever looks at what this user lifted.
     *
     * @return array{repetition: list<int>, weight: list<float>}
     */
    public function getValues(Exercise $exercise, User $user): array
    {
        $sequences = Sequence::query()
            ->whereHas('activity', fn (Builder $query) => $query
                ->where('exercise_id', $exercise->id)
                ->whereHas('training', fn (Builder $query) => $query->where('user_id', $user->id)))
            ->latest('id')
            ->limit(self::CHART_LENGTH)
            ->get()
            ->reverse();

        return [
            'repetition' => array_values($sequences->map(fn (Sequence $sequence): int => $sequence->repetition)->all()),
            'weight' => array_values($sequences->map(fn (Sequence $sequence): float => $sequence->weight ?? 0.0)->all()),
        ];
    }

    /**
     * A slug no other exercise holds, since the library and the users' own
     * entries share one namespace.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'exercise';
        $slug = $base;
        $suffix = 2;

        while (Exercise::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * The ids the engine matched, or null when it answered with an error.
     *
     * @return array<int, string>|null
     */
    private function hits(string $term): ?array
    {
        try {
            // The engine hands keys back as strings whatever the driver.
            return Exercise::search($term)
                ->take(self::HITS)
                ->keys()
                ->map(fn (mixed $key): string => (string) $key)
                ->all();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Keep the engine's ranking: it put the best match first, and a caller
     * ordering by name afterwards would only break ties.
     *
     * @param  Builder<Exercise>  $query
     * @param  array<int, string>  $hits
     * @return Builder<Exercise>
     */
    private function inHitOrder(Builder $query, array $hits): Builder
    {
        if ($hits === []) {
            return $query;
        }

        $bindings = [];
        $case = 'case exercises.id';

        foreach (array_values($hits) as $rank => $id) {
            $case .= ' when ? then ?';
            $bindings[] = $id;
            $bindings[] = $rank;
        }

        return $query->orderByRaw($case.' else ? end', [...$bindings, count($hits)]);
    }
}
