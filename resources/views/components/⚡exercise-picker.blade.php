<?php

use App\Models\Exercise;
use App\Services\ExerciseService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * How many results to show before asking the user to narrow the search.
     */
    protected const int LIMIT = 24;

    public string $term = '';

    public ?string $muscle = null;

    public ?string $equipment = null;

    /**
     * Which slice of the library is on screen: favourites, the exercises the
     * user added, the imported ones, or everything.
     */
    public ?string $source = null;

    /**
     * Matching entries from the shared library.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Exercise>
     */
    #[Computed]
    public function results()
    {
        return $this->query()
            ->with('media')
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * How many entries match, so the list can say what it is not showing.
     */
    #[Computed]
    public function total(): int
    {
        return $this->query()->count();
    }

    /**
     * The library narrowed by whatever the user is filtering on.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Exercise>
     */
    private function query()
    {
        $query = app(ExerciseService::class)
            ->applyTerm(Exercise::query()->availableTo(auth()->user()), $this->term);

        return $query
            ->forMuscle($this->muscle)
            ->withEquipment($this->equipment)
            ->when($this->source === 'favorites', fn ($query) => $query->favoritedBy(auth()->user()))
            ->when($this->source === 'custom', fn ($query) => $query->custom())
            ->when($this->source === 'imported', fn ($query) => $query->imported());
    }

    /**
     * Narrow the list to one slice of the library, or drop the filter when the
     * same one is tapped again.
     */
    public function filterSource(string $source): void
    {
        $this->source = $this->source === $source ? null : $source;

        unset($this->results, $this->total);
    }

    /**
     * Toggle a muscle filter off when it is tapped again.
     */
    public function filterMuscle(string $muscle): void
    {
        $this->muscle = $this->muscle === $muscle ? null : $muscle;

        unset($this->results, $this->total);
    }

    /**
     * Toggle an equipment filter off when it is tapped again.
     */
    public function filterEquipment(string $equipment): void
    {
        $this->equipment = $this->equipment === $equipment ? null : $equipment;

        unset($this->results, $this->total);
    }

    /**
     * Hand the chosen exercise to whoever embedded the picker.
     */
    public function choose(int $id): void
    {
        $exercise = Exercise::findOrFail($id);

        $this->dispatch('exercise-chosen', id: $exercise->id, name: $exercise->name);
    }

    /**
     * Hand over whatever the user typed, for an exercise the library lacks.
     */
    public function chooseTyped(): void
    {
        if (blank($this->term)) {
            return;
        }

        $this->dispatch('exercise-chosen', id: null, name: trim($this->term));
    }
};
?>

<div>
    <x-field :label="__('Search the library')" for="exercise-search">
        <x-input
            id="exercise-search"
            type="search"
            wire:model.live.debounce.300ms="term"
            placeholder="{{ __('Bench press, squat…') }}"
            autocomplete="off"
        />
    </x-field>

    <div class="mb-3 -mt-2 flex flex-wrap gap-1.5">
        <button
            type="button"
            wire:click="filterSource('favorites')"
            @class([
                'flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold transition',
                'bg-accent text-accent-ink' => $source === 'favorites',
                'bg-raised text-ink-soft hover:text-ink' => $source !== 'favorites',
            ])
        >
            <x-heroicon-s-heart class="size-3.5" />
            {{ __('Favorites') }}
        </button>

        <button
            type="button"
            wire:click="filterSource('custom')"
            @class([
                'rounded-full px-2.5 py-1 text-xs font-bold transition',
                'bg-accent text-accent-ink' => $source === 'custom',
                'bg-raised text-ink-soft hover:text-ink' => $source !== 'custom',
            ])
        >
            {{ __('Custom') }}
        </button>

        <button
            type="button"
            wire:click="filterSource('imported')"
            @class([
                'rounded-full px-2.5 py-1 text-xs font-bold transition',
                'bg-accent text-accent-ink' => $source === 'imported',
                'bg-raised text-ink-soft hover:text-ink' => $source !== 'imported',
            ])
        >
            {{ __('Imported') }}
        </button>

        @foreach (\App\Models\Exercise::MUSCLES as $muscle)
            <button
                type="button"
                wire:key="muscle-{{ $muscle }}"
                wire:click="filterMuscle(@js($muscle))"
                @class([
                    'rounded-full px-2.5 py-1 text-xs font-bold transition',
                    'bg-accent text-accent-ink' => $this->muscle === $muscle,
                    'bg-raised text-ink-soft hover:text-ink' => $this->muscle !== $muscle,
                ])
            >
                {{ __('muscle.'.$muscle) }}
            </button>
        @endforeach
    </div>

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach (\App\Models\Exercise::EQUIPMENTS as $equipment)
            <button
                type="button"
                wire:key="equipment-{{ $equipment }}"
                wire:click="filterEquipment(@js($equipment))"
                @class([
                    'rounded-full px-2.5 py-1 text-xs font-bold transition',
                    'bg-accent text-accent-ink' => $this->equipment === $equipment,
                    'bg-raised text-ink-soft hover:text-ink' => $this->equipment !== $equipment,
                ])
            >
                {{ __('equipment.'.$equipment) }}
            </button>
        @endforeach
    </div>

    <ul wire:loading.class="opacity-50">
        @forelse ($this->results as $exercise)
            <li wire:key="exercise-{{ $exercise->id }}" class="border-b border-line last:border-0">
                <button type="button" wire:click="choose({{ $exercise->id }})" class="flex w-full items-center gap-3 py-3 text-left">
                    @if ($exercise->hasIllustrations())
                        <img
                            src="{{ $exercise->getFirstMediaUrl(\App\Models\Exercise::ILLUSTRATIONS, 'thumb') }}"
                            alt=""
                            loading="lazy"
                            class="size-12 shrink-0 rounded-xl bg-raised object-cover"
                        >
                    @else
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-raised">
                            <x-heroicon-o-bolt class="size-5 text-ink-muted" />
                        </span>
                    @endif

                    <span class="min-w-0 flex-1">
                    <span class="block font-bold text-ink">{{ $exercise->name }}</span>

                    <span class="mt-1 flex flex-wrap gap-1.5">
                        @foreach ($exercise->primary_muscles as $muscle)
                            <x-badge>{{ __('muscle.'.$muscle) }}</x-badge>
                        @endforeach

                        @if ($exercise->equipment)
                            <span class="rounded-full bg-raised px-2.5 py-0.5 text-xs font-bold text-ink-soft">
                                {{ __('equipment.'.$exercise->equipment) }}
                            </span>
                        @endif
                    </span>
                    </span>
                </button>
            </li>
        @empty
            <li class="py-6 text-center">
                <p class="text-sm font-semibold text-ink-muted">{{ __('No exercise matches.') }}</p>

                @if (filled($term))
                    <x-button type="button" variant="secondary" size="sm" class="mt-3" wire:click="chooseTyped">
                        <x-heroicon-o-plus class="size-4" />
                        {{ __('Use ":name"', ['name' => $term]) }}
                    </x-button>
                @endif
            </li>
        @endforelse
    </ul>

    @if ($this->total > $this->results->count())
        <p class="pt-3 text-xs font-semibold text-ink-muted">
            {{ __(':shown of :total shown. Narrow your search.', ['shown' => $this->results->count(), 'total' => $this->total]) }}
        </p>
    @endif
</div>
