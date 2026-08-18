<?php

use App\Models\Activity;
use App\Models\Training;
use App\Services\ExerciseService;
use App\Services\TrainingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Training $training;

    public ?int $deletingId = null;

    /**
     * Whether the exercise search is open.
     */
    public bool $picking = false;

    /**
     * Load the training the route points at.
     */
    public function mount(Training $training): void
    {
        $this->authorize('view', $training);

        $this->training = $training;
    }

    /**
     * Render the page, showing the training's name in the browser tab.
     */
    public function render()
    {
        return $this->view()->title((string) $this->training->name);
    }

    /**
     * The training's activities, most recent first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    #[Computed]
    public function activities()
    {
        return $this->training->activities()
            ->with('exercise')
            ->withCount('sequences')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * How many activities the user still has to tick off.
     */
    #[Computed]
    public function remaining(): int
    {
        return $this->activities->reject(fn (Activity $activity): bool => $activity->isCompleted())->count();
    }

    /**
     * How much of the session is done, as a percentage.
     */
    #[Computed]
    public function progress(): int
    {
        if ($this->activities->isEmpty()) {
            return 0;
        }

        return (int) round(($this->activities->count() - $this->remaining) / $this->activities->count() * 100);
    }

    /**
     * The exercises this user already logged, offered before the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Exercise>
     */
    #[Computed]
    public function suggestions()
    {
        return app(ExerciseService::class)->logged(auth()->user());
    }

    /**
     * Open the exercise search over the session.
     */
    public function pick(): void
    {
        $this->authorize('update', $this->training);

        $this->picking = true;
    }

    /**
     * Close the exercise search without adding anything.
     */
    public function closeModal(): void
    {
        $this->picking = false;
    }

    /**
     * Add the chosen exercise to the session and stay on the list, so several
     * exercises can be added one after the other.
     */
    #[On('exercise-chosen')]
    public function addActivity(?int $id, string $name, TrainingService $trainings): void
    {
        $this->authorize('update', $this->training);

        $exercise = app(ExerciseService::class)->resolve(auth()->user(), $id, $name);

        $this->training->activities()->create(['exercise_id' => $exercise->id]);

        $trainings->syncCompletion($this->training);

        $this->training->refresh();

        $this->picking = false;

        unset($this->activities, $this->remaining, $this->progress, $this->suggestions);
    }

    /**
     * Tick an activity off, or put it back on the to-do list.
     */
    public function toggle(int $id, TrainingService $trainings): void
    {
        $activity = Activity::findOrFail($id);

        $this->authorize('update', $activity);

        $activity->isCompleted() ? $activity->reopen() : $activity->complete();

        $trainings->syncCompletion($this->training);

        $this->training->refresh();

        unset($this->activities, $this->remaining, $this->progress);
    }

    /**
     * Close the session, counting whatever is left as done.
     */
    public function complete(TrainingService $trainings): void
    {
        $this->authorize('update', $this->training);

        if ($this->activities->isEmpty()) {
            return;
        }

        $trainings->complete($this->training);

        $this->training->refresh();

        unset($this->activities, $this->remaining, $this->progress);
    }

    /**
     * Reopen the session so it can be corrected.
     */
    public function reopen(TrainingService $trainings): void
    {
        $this->authorize('update', $this->training);

        $trainings->reopen($this->training);

        $this->training->refresh();
    }

    /**
     * Ask the user to confirm deleting an activity.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    /**
     * Dismiss the delete confirmation.
     */
    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    /**
     * Delete the activity the user confirmed.
     */
    public function delete(TrainingService $trainings): void
    {
        $activity = Activity::findOrFail($this->deletingId);

        $this->authorize('delete', $activity);

        $activity->delete();

        $trainings->syncCompletion($this->training);

        $this->training->refresh();

        $this->deletingId = null;

        unset($this->activities, $this->remaining, $this->progress);
    }
};
?>

<div>
    <x-page-header
        :title="$training->name"
        :subtitle="$training->date->translatedFormat('l j F Y')"
        :back="route('trainings.index')"
    >
        <x-slot:actions>
            @if ($training->isCompleted())
                <x-button type="button" variant="secondary" wire:click="reopen">
                    <x-heroicon-o-arrow-uturn-left class="size-4" />
                    {{ __('Reopen') }}
                </x-button>
            @else
                {{-- Quiet until there is nothing left to tick off, loud once there is. --}}
                <x-button
                    type="button"
                    :variant="$this->activities->isNotEmpty() && $this->remaining === 0 ? 'primary' : 'secondary'"
                    wire:click="complete"
                    :disabled="$this->activities->isEmpty()"
                >
                    <x-heroicon-o-check class="size-4" />
                    {{-- The full wording would eat the title on a phone. --}}
                    <span class="sm:hidden">{{ __('Finish') }}</span>
                    <span class="max-sm:hidden">{{ __('Finish session') }}</span>
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($this->activities->isNotEmpty())
        {{-- Where the session stands, so the next tap is obvious. --}}
        @if ($training->isCompleted())
            <div class="mb-6 flex items-center gap-3">
                <x-badge>
                    <x-heroicon-s-check-circle class="size-3.5" />
                    {{ __('Session completed') }}
                </x-badge>

                <span class="text-sm font-semibold text-ink-muted">
                    {{ $training->completed_at->translatedFormat('j F Y, H:i') }}
                </span>
            </div>
        @else
            <div class="mb-6">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-raised">
                    <div class="h-full rounded-full bg-accent transition-all duration-300" style="width: {{ $this->progress }}%"></div>
                </div>

                <p class="mt-2 text-sm font-semibold text-ink-muted">
                    {{ trans_choice(':count exercise left|:count exercises left', $this->remaining, ['count' => $this->remaining]) }}
                </p>
            </div>
        @endif

        <ul>
            @foreach ($this->activities as $activity)
                <li wire:key="activity-{{ $activity->id }}" class="group flex items-center gap-3 border-b border-line last:border-0">
                    {{-- The tick is the session's main gesture, so it leads the row. --}}
                    <button
                        type="button"
                        wire:click="toggle({{ $activity->id }})"
                        class="shrink-0 rounded-full p-1 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        aria-pressed="{{ $activity->isCompleted() ? 'true' : 'false' }}"
                        aria-label="{{ $activity->isCompleted() ? __('Mark as not done') : __('Mark as done') }}"
                    >
                        @if ($activity->isCompleted())
                            <x-heroicon-s-check-circle class="size-6 text-accent" />
                        @else
                            <x-heroicon-o-check-circle class="size-6 text-ink-muted transition group-hover:text-ink-soft" />
                        @endif
                    </button>

                    <a href="{{ route('activities.show', $activity) }}" wire:navigate class="min-w-0 flex-1 py-4">
                        <div @class([
                            'truncate font-bold',
                            'text-ink-muted line-through' => $activity->isCompleted(),
                            'text-ink' => ! $activity->isCompleted(),
                        ])>{{ $activity->exercise->name }}</div>

                        <div class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $activity->sequences_formatted }}</div>
                    </a>

                    <x-heroicon-o-chevron-right class="size-4 shrink-0 text-ink-muted" />

                    <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            wire:click="confirmDelete({{ $activity->id }})"
                            aria-label="{{ __('Delete activity') }}"
                        >
                            <x-heroicon-o-trash class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>

        <x-add-row class="mt-4 w-full" as="button" type="button" wire:click="pick" :label="__('Add an exercise')" />
    @else
        <x-empty-state icon="o-chart-bar">
            {{ __('No activity in this training yet.') }}

            <x-slot:action>
                <x-button type="button" wire:click="pick">
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('Add an exercise') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endif

    <x-modal :show="$picking" :title="__('Add an exercise')">
        @if ($this->suggestions->isNotEmpty())
            <x-section-heading icon="o-fire">{{ __('Your most used') }}</x-section-heading>

            <div class="mb-5 flex flex-wrap gap-2">
                @foreach ($this->suggestions as $exercise)
                    <button
                        type="button"
                        wire:key="suggestion-{{ $exercise->id }}"
                        wire:click="addActivity({{ $exercise->id }}, @js($exercise->name))"
                        class="rounded-full bg-raised px-3 py-1.5 text-sm font-bold text-ink-soft transition hover:bg-accent-soft hover:text-accent"
                    >
                        {{ $exercise->name }}
                    </button>
                @endforeach
            </div>
        @endif

        <livewire:exercise-picker />
    </x-modal>

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this activity?')"
        :message="__('Its sequences will be deleted too.')"
    />
</div>
