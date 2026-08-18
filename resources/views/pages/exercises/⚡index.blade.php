<?php

use App\Models\Exercise;
use App\Services\ExerciseService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * How many more entries each tap on "load more" reveals.
     */
    protected const int PAGE = 24;

    public string $term = '';

    public ?string $muscle = null;

    public ?string $equipment = null;

    /**
     * Which slice of the library is on screen: favourites, the exercises the
     * user added, the imported ones, or everything.
     */
    public ?string $source = null;

    /**
     * How many entries are currently on screen. The library holds hundreds, so
     * it is revealed a page at a time rather than all at once.
     */
    public int $shown = self::PAGE;

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
            ->limit($this->shown)
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
     * The ids the user pinned, to mark the rows without a query per row.
     *
     * @return list<int>
     */
    #[Computed]
    public function favorites(): array
    {
        return auth()->user()->favoriteExercises()->pluck('exercises.id')->all();
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
     * Toggle a muscle filter off when it is tapped again.
     */
    public function filterMuscle(string $muscle): void
    {
        $this->muscle = $this->muscle === $muscle ? null : $muscle;

        $this->rewind();
    }

    /**
     * Toggle an equipment filter off when it is tapped again.
     */
    public function filterEquipment(string $equipment): void
    {
        $this->equipment = $this->equipment === $equipment ? null : $equipment;

        $this->rewind();
    }

    /**
     * Narrow the list to one slice of the library, or drop the filter when the
     * same one is tapped again.
     */
    public function filterSource(string $source): void
    {
        $this->source = $this->source === $source ? null : $source;

        $this->rewind();
    }

    /**
     * A new search starts from the top of the list.
     */
    public function updatedTerm(): void
    {
        $this->rewind();
    }

    /**
     * Reveal the next page of the library.
     */
    public function loadMore(): void
    {
        $this->shown += self::PAGE;

        unset($this->results);
    }

    /**
     * Go back to the first page, after the filters changed under the list.
     */
    private function rewind(): void
    {
        $this->shown = self::PAGE;

        unset($this->results, $this->total);
    }

    /**
     * Pin an exercise, or unpin it when it already is.
     */
    public function toggleFavorite(int $id): void
    {
        auth()->user()->favoriteExercises()->toggle(Exercise::findOrFail($id));

        unset($this->favorites, $this->results, $this->total);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Exercises'));
    }
};
?>

<div>
    <x-page-header :title="__('Exercises')" :subtitle="__('The shared library, plus your own. Pin the ones you train.')" />

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

        @foreach (\App\Models\Exercise::MUSCLES as $muscleFilter)
            <button
                type="button"
                wire:key="muscle-{{ $muscleFilter }}"
                wire:click="filterMuscle(@js($muscleFilter))"
                @class([
                    'rounded-full px-2.5 py-1 text-xs font-bold transition',
                    'bg-accent text-accent-ink' => $muscle === $muscleFilter,
                    'bg-raised text-ink-soft hover:text-ink' => $muscle !== $muscleFilter,
                ])
            >
                {{ __('muscle.'.$muscleFilter) }}
            </button>
        @endforeach
    </div>

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach (\App\Models\Exercise::EQUIPMENTS as $equipmentFilter)
            <button
                type="button"
                wire:key="equipment-{{ $equipmentFilter }}"
                wire:click="filterEquipment(@js($equipmentFilter))"
                @class([
                    'rounded-full px-2.5 py-1 text-xs font-bold transition',
                    'bg-accent text-accent-ink' => $equipment === $equipmentFilter,
                    'bg-raised text-ink-soft hover:text-ink' => $equipment !== $equipmentFilter,
                ])
            >
                {{ __('equipment.'.$equipmentFilter) }}
            </button>
        @endforeach
    </div>

    <ul wire:loading.class="opacity-50">
        @forelse ($this->results as $exercise)
            @php($isFavorite = in_array($exercise->id, $this->favorites, true))

            <li wire:key="exercise-{{ $exercise->id }}" class="flex items-center gap-3 border-b border-line last:border-0">
                <a href="{{ route('exercises.show', $exercise) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3 py-3">
                    @if ($exercise->hasIllustrations())
                        <img
                            src="{{ $exercise->getFirstMediaUrl(Exercise::ILLUSTRATIONS, 'thumb') }}"
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
                            @foreach ($exercise->primary_muscles as $primaryMuscle)
                                <x-badge>{{ __('muscle.'.$primaryMuscle) }}</x-badge>
                            @endforeach

                            @if ($exercise->equipment)
                                <span class="rounded-full bg-raised px-2.5 py-0.5 text-xs font-bold text-ink-soft">
                                    {{ __('equipment.'.$exercise->equipment) }}
                                </span>
                            @endif
                        </span>
                    </span>
                </a>

                <button
                    type="button"
                    wire:click="toggleFavorite({{ $exercise->id }})"
                    class="shrink-0 rounded-full p-2 transition hover:bg-raised focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                    aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                    aria-label="{{ $isFavorite ? __('Remove from favorites') : __('Add to favorites') }}"
                >
                    @if ($isFavorite)
                        <x-heroicon-s-heart class="size-5 text-accent" />
                    @else
                        <x-heroicon-o-heart class="size-5 text-ink-muted" />
                    @endif
                </button>
            </li>
        @empty
            <li>
                <x-empty-state icon="o-book-open">
                    {{ $source === 'favorites' ? __('No favorite yet. Tap the heart on an exercise to pin it.') : __('No exercise matches.') }}
                </x-empty-state>
            </li>
        @endforelse
    </ul>

    <x-add-row class="mt-5" :href="route('exercises.create')" wire:navigate :label="__('Add an exercise the library lacks')" />

    @if ($this->total > $this->results->count())
        <div class="pt-5 text-center">
            <x-button type="button" variant="secondary" wire:click="loadMore">
                <x-heroicon-o-arrow-down class="size-4" wire:loading.remove wire:target="loadMore" />
                <x-heroicon-o-arrow-path class="size-4 animate-spin" wire:loading wire:target="loadMore" />
                {{ __('Load more') }}
            </x-button>

            <p class="numeric mt-3 text-xs font-semibold text-ink-muted">
                {{ __(':shown of :total', ['shown' => $this->results->count(), 'total' => $this->total]) }}
            </p>
        </div>
    @elseif ($this->results->isNotEmpty())
        <p class="numeric pt-4 text-center text-xs font-semibold text-ink-muted">
            {{ trans_choice(':count exercise|:count exercises', $this->total, ['count' => $this->total]) }}
        </p>
    @endif
</div>
