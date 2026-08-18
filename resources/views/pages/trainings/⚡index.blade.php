<?php

use App\Models\Training;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $deletingId = null;

    /**
     * The user's trainings, most recent first, grouped by the month they
     * happened in so the list reads like a journal.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, Training>>
     */
    #[Computed]
    public function trainingsByMonth()
    {
        return auth()->user()->trainings()
            ->withCount([
                'activities',
                'activities as completed_activities_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])
            ->orderByDesc('date')
            ->get()
            ->groupBy(fn (Training $training): string => $training->date->translatedFormat('F Y'));
    }

    /**
     * Ask the user to confirm deleting a training.
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
     * Delete the training the user confirmed.
     */
    public function delete(): void
    {
        $training = Training::findOrFail($this->deletingId);

        $this->authorize('delete', $training);

        $training->delete();

        $this->deletingId = null;

        unset($this->trainingsByMonth);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Trainings'));
    }
};
?>

<div>
    <x-page-header :title="__('Trainings')" />

    @if ($this->trainingsByMonth->isNotEmpty())
        <x-add-row class="mb-8" :href="route('trainings.create')" wire:navigate :label="__('Add a training')" />
    @endif

    @forelse ($this->trainingsByMonth as $month => $trainings)
        <section class="mb-8" wire:key="month-{{ $loop->index }}">
            <x-section-heading icon="o-calendar-days">{{ $month }}</x-section-heading>

            <ul>
                @foreach ($trainings as $training)
                    <li wire:key="training-{{ $training->id }}" class="flex items-center gap-4 border-b border-line last:border-0">
                        {{-- The date rail carries the structure of the list. --}}
                        <div class="numeric w-10 shrink-0 text-center">
                            <div class="text-xl leading-none font-extrabold text-accent">{{ $training->date->format('d') }}</div>
                            <div class="mt-1 text-[10px] font-bold tracking-wide text-ink-muted uppercase">{{ $training->date->translatedFormat('M') }}</div>
                        </div>

                        <a href="{{ route('trainings.show', $training) }}" wire:navigate class="min-w-0 flex-1 py-4">
                            <div class="truncate font-bold text-ink">{{ $training->name }}</div>

                            <div class="mt-0.5 flex items-center gap-2 text-sm font-semibold text-ink-soft">
                                <span class="truncate">{{ $training->activities_formatted }}</span>

                                {{-- An open session is the one the user is likely coming back to. --}}
                                @if (! $training->isCompleted() && $training->activities_count > 0)
                                    <x-badge class="numeric shrink-0">
                                        <x-heroicon-s-play-circle class="size-3.5" />
                                        {{ __('In progress') }} {{ $training->progress_formatted }}
                                    </x-badge>
                                @endif
                            </div>
                        </a>

                        <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            wire:click="confirmDelete({{ $training->id }})"
                            aria-label="{{ __('Delete training') }}"
                        >
                            <x-heroicon-o-trash class="size-4" />
                        </x-button>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <x-empty-state icon="o-bolt">
            {{ __('No training recorded yet.') }}

            <x-slot:action>
                <x-button :href="route('trainings.create')" as="a" wire:navigate>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('New training') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endforelse

    <x-fab :href="route('trainings.create')" wire:navigate :label="__('New training')" />

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this training?')"
        :message="__('Its activities and sequences will be deleted too.')"
    />
</div>
