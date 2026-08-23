<?php

use App\Models\Exercise;
use App\Services\ExerciseService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Exercise $exercise;

    /**
     * Load the exercise the route points at.
     */
    public bool $confirmingDelete = false;

    public function mount(Exercise $exercise): void
    {
        $this->authorize('view', $exercise);

        $this->exercise = $exercise->load('media');
    }

    /**
     * Whether the user pinned this exercise.
     */
    #[Computed]
    public function isFavorite(): bool
    {
        return auth()->user()->favoriteExercises()->whereKey($this->exercise->id)->exists();
    }

    /**
     * How the user's own numbers moved on this exercise, oldest first.
     *
     * @return array{labels: list<string>, datasets: list<array{label: string, data: list<int|float>}>}|null
     */
    #[Computed]
    public function progress(): ?array
    {
        $values = app(ExerciseService::class)->getValues($this->exercise, auth()->user());

        if ($values['weight'] === []) {
            return null;
        }

        return [
            // Sequences have no meaningful date, so they are numbered.
            'labels' => array_map(fn (int $index): string => (string) ($index + 1), array_keys($values['weight'])),
            'datasets' => [
                ['label' => __('Weight'), 'data' => $values['weight']],
                ['label' => __('Repetition'), 'data' => $values['repetition']],
            ],
        ];
    }

    /**
     * The exercises either side of this one, so the library can be walked from
     * here. They follow the filters the index is left on, not the raw library:
     * someone stepping through "chest, dumbbell" stays inside that list.
     *
     * @return array{previous: Exercise|null, next: Exercise|null}
     */
    #[Computed]
    public function neighbours(): array
    {
        $exercises = app(ExerciseService::class);

        return $exercises->neighbours($this->exercise, auth()->user(), $exercises->sessionFilters());
    }

    /**
     * Ask the user to confirm deleting their own exercise.
     */
    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    /**
     * Dismiss the delete confirmation.
     */
    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    /**
     * Delete the exercise the user added.
     */
    public function delete(): void
    {
        $this->authorize('delete', $this->exercise);

        $this->exercise->delete();

        $this->redirect(route('exercises.index'), navigate: true);
    }

    /**
     * Pin the exercise, or unpin it when it already is.
     */
    public function toggleFavorite(): void
    {
        auth()->user()->favoriteExercises()->toggle($this->exercise);

        unset($this->isFavorite);
    }

    /**
     * Render the page, showing the exercise name in the browser tab.
     */
    public function render()
    {
        return $this->view()->title($this->exercise->name);
    }
};
?>

<div>
    <x-page-header :title="$exercise->name" :back="route('exercises.index')">
        <x-slot:actions>
            @can('delete', $exercise)
                <x-button type="button" variant="quiet-danger" size="icon" wire:click="confirmDelete" aria-label="{{ __('Delete exercise') }}">
                    <x-heroicon-o-trash class="size-4" />
                </x-button>
            @endcan

            <x-button type="button" :variant="$this->isFavorite ? 'primary' : 'secondary'" wire:click="toggleFavorite">
                @if ($this->isFavorite)
                    <x-heroicon-s-heart class="size-4" />
                    {{ __('Favorite') }}
                @else
                    <x-heroicon-o-heart class="size-4" />
                    {{ __('Add to favorites') }}
                @endif
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($exercise->hasIllustrations())
        <div class="mb-8 grid gap-3 sm:grid-cols-2">
            @foreach ($exercise->getMedia(Exercise::ILLUSTRATIONS) as $illustration)
                {{-- Never blown up past its own pixels: the sources top out
                     around 850px wide, and stretched to the column they turn
                     to mush. Down to the column, yes; up, never. --}}
                <img
                    src="{{ $illustration->getUrl() }}"
                    alt="{{ $exercise->name }}"
                    loading="lazy"
                    class="mx-auto max-h-72 w-auto max-w-full rounded-2xl bg-raised object-contain"
                >
            @endforeach
        </div>
    @endif

    {{-- What the movement is, in one row of chips. --}}
    <div class="mb-8 flex flex-wrap gap-1.5">
        @if ($exercise->isCustom())
            <span class="rounded-full bg-accent-soft px-2.5 py-1 text-xs font-bold text-accent">{{ __('Your exercise') }}</span>
        @endif

        @if ($exercise->equipment)
            <span class="rounded-full bg-raised px-2.5 py-1 text-xs font-bold text-ink-soft">
                {{ __('equipment.'.$exercise->equipment) }}
            </span>
        @endif

        @foreach (array_filter([$exercise->category, $exercise->level, $exercise->force, $exercise->mechanic]) as $trait)
            <span class="rounded-full bg-raised px-2.5 py-1 text-xs font-bold text-ink-soft">{{ ucfirst($trait) }}</span>
        @endforeach
    </div>

    @if ($this->progress !== null)
        <section class="mb-8">
            <x-section-heading icon="o-chart-bar">{{ __('Your progress') }}</x-section-heading>

            <x-chart :labels="$this->progress['labels']" :datasets="$this->progress['datasets']" height="h-40" />
        </section>
    @endif

    @if ($exercise->primary_muscles !== [])
        <section class="mb-8">
            <x-section-heading icon="o-fire">{{ __('Primary muscles') }}</x-section-heading>

            <div class="flex flex-wrap gap-1.5">
                @foreach ($exercise->primary_muscles as $muscle)
                    <x-badge>{{ __('muscle.'.$muscle) }}</x-badge>
                @endforeach
            </div>
        </section>
    @endif

    @if ($exercise->secondary_muscles !== [])
        <section class="mb-8">
            <x-section-heading icon="o-sparkles">{{ __('Secondary muscles') }}</x-section-heading>

            <div class="flex flex-wrap gap-1.5">
                @foreach ($exercise->secondary_muscles as $muscle)
                    <span class="rounded-full bg-raised px-2.5 py-0.5 text-xs font-bold text-ink-soft">
                        {{ __('muscle.'.$muscle) }}
                    </span>
                @endforeach
            </div>
        </section>
    @endif

    @php($instructionSteps = $exercise->instructionSteps())

    @if ($instructionSteps !== [])
        <section>
            <x-section-heading icon="o-list-bullet">{{ __('Instructions') }}</x-section-heading>

            <ol>
                @foreach ($instructionSteps as $instruction)
                    <li wire:key="step-{{ $loop->index }}" class="flex gap-3 border-b border-line py-3 last:border-0">
                        <span class="numeric w-5 shrink-0 text-sm font-bold text-ink-muted">{{ $loop->iteration }}</span>
                        <span class="text-sm font-semibold text-ink-soft">{{ $instruction }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @php($previous = $this->neighbours['previous'])
    @php($next = $this->neighbours['next'])

    @if ($previous || $next)
        {{-- Stepping to the neighbour beats going back to the list and finding
             one's place in it again. Both sides keep their half of the row on
             desktop, so "next" never slides under the back chevron. --}}
        <nav class="mt-10 grid gap-3 border-t border-line pt-6 sm:grid-cols-2" aria-label="{{ __('Browse the library') }}">
            @if ($previous)
                <a
                    href="{{ route('exercises.show', $previous) }}"
                    wire:navigate
                    class="flex min-w-0 items-center gap-3 rounded-2xl bg-raised px-4 py-3 transition hover:bg-accent-soft"
                >
                    <x-heroicon-o-chevron-left class="size-5 shrink-0 text-ink-muted" />

                    <span class="min-w-0">
                        <span class="block text-[11.5px] font-bold tracking-[0.09em] text-ink-muted uppercase">{{ __('Previous') }}</span>
                        <span class="block truncate font-bold text-ink">{{ $previous->name }}</span>
                    </span>
                </a>
            @endif

            @if ($next)
                <a
                    href="{{ route('exercises.show', $next) }}"
                    wire:navigate
                    class="flex min-w-0 items-center justify-end gap-3 rounded-2xl bg-raised px-4 py-3 text-right transition hover:bg-accent-soft sm:col-start-2"
                >
                    <span class="min-w-0">
                        <span class="block text-[11.5px] font-bold tracking-[0.09em] text-ink-muted uppercase">{{ __('Next') }}</span>
                        <span class="block truncate font-bold text-ink">{{ $next->name }}</span>
                    </span>

                    <x-heroicon-o-chevron-right class="size-5 shrink-0 text-ink-muted" />
                </a>
            @endif
        </nav>
    @endif

    <x-confirm-delete
        :show="$confirmingDelete"
        :title="__('Delete this exercise?')"
        :message="__('The activities recorded under it will be deleted too.')"
    />
</div>
