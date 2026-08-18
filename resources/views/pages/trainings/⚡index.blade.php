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
            ->withCount('activities')
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
    <x-page-header :title="__('Trainings')">
        <x-slot:actions>
            <x-button :href="route('trainings.create')" as="a" wire:navigate class="max-sm:hidden">
                <x-heroicon-o-plus class="size-4" />
                {{ __('New training') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @forelse ($this->trainingsByMonth as $month => $trainings)
        <section class="mb-8" wire:key="month-{{ $loop->index }}">
            <x-section-heading>{{ $month }}</x-section-heading>

            <ul>
                @foreach ($trainings as $training)
                    <li wire:key="training-{{ $training->id }}" class="group flex items-center gap-4 border-b border-line last:border-0">
                        {{-- The date rail carries the structure of the list. --}}
                        <div class="numeric w-10 shrink-0 text-center">
                            <div class="text-xl leading-none font-extrabold text-accent">{{ $training->date->format('d') }}</div>
                            <div class="mt-1 text-[10px] font-bold tracking-wide text-ink-muted uppercase">{{ $training->date->translatedFormat('M') }}</div>
                        </div>

                        <a href="{{ route('trainings.show', $training) }}" wire:navigate class="min-w-0 flex-1 py-4">
                            <div class="truncate font-bold text-ink">{{ $training->name }}</div>
                            <div class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $training->activities_formatted }}</div>
                        </a>

                        <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                            wire:click="confirmDelete({{ $training->id }})"
                            aria-label="{{ __('Delete training') }}"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
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
