<?php

use App\Models\Activity;
use App\Models\Sequence;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Activity $activity;

    public ?int $deletingId = null;

    /**
     * Load the activity the route points at.
     */
    public function mount(Activity $activity): void
    {
        $this->authorize('view', $activity);

        $this->activity = $activity->load('exercise', 'training');
    }

    /**
     * Render the page, showing the activity type in the browser tab.
     */
    public function render()
    {
        return $this->view()->title($this->activity->exercise->name);
    }

    /**
     * The sequences recorded for this activity, in the order performed.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Sequence>
     */
    #[Computed]
    public function sequences()
    {
        return $this->activity->sequences()->orderBy('id')->get();
    }

    /**
     * Ask the user to confirm deleting a sequence.
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
     * Delete the sequence the user confirmed.
     */
    public function delete(): void
    {
        $sequence = Sequence::findOrFail($this->deletingId);

        $this->authorize('delete', $sequence);

        $sequence->delete();

        $this->deletingId = null;

        unset($this->sequences);
    }
};
?>

<div>
    <x-page-header
        :title="$activity->exercise->name"
        :subtitle="$activity->training->date->translatedFormat('j F Y')"
        :back="route('trainings.show', $activity->training)"
    />

    @if ($activity->isCompleted())
        <div class="mb-5 flex items-center gap-3">
            <x-badge>
                <x-heroicon-s-check-circle class="size-3.5" />
                {{ __('Done') }}
            </x-badge>

            <span class="text-sm font-semibold text-ink-muted">
                {{ $activity->completed_at->translatedFormat('j F Y, H:i') }}
            </span>
        </div>
    @endif

    @if ($this->sequences->isNotEmpty())
        <ul>
            @foreach ($this->sequences as $sequence)
                {{-- The load is the point of the row, so it gets the size. --}}
                <li wire:key="sequence-{{ $sequence->id }}" class="flex items-center gap-4 border-b border-line py-4 last:border-0">
                    <span class="numeric w-6 shrink-0 text-sm font-bold text-ink-muted">{{ $loop->iteration }}</span>

                    <div class="flex min-w-0 flex-1 items-baseline gap-1.5">
                        @if ($sequence->weight !== null)
                            <span class="numeric text-2xl leading-none font-extrabold text-ink">{{ $sequence->weight_formatted }}</span>
                            <span class="text-sm font-semibold text-ink-muted">{{ $sequence->unit->label() }}</span>
                        @else
                            <span class="font-semibold text-ink-soft">{{ __('Bodyweight') }}</span>
                        @endif
                    </div>

                    <x-badge class="numeric shrink-0">&times;&nbsp;{{ $sequence->repetition }}</x-badge>

                    <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            wire:click="confirmDelete({{ $sequence->id }})"
                            aria-label="{{ __('Delete sequence') }}"
                        >
                            <x-heroicon-o-trash class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>

        <x-add-row class="mt-4" :href="route('sequences.create', $activity)" wire:navigate :label="__('Add a set')" />
    @else
        <x-empty-state icon="o-bolt">
            {{ __('No sequence recorded yet.') }}

            <x-slot:action>
                <x-button :href="route('sequences.create', $activity)" as="a" wire:navigate>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('New sequence') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endif

    <x-confirm-delete :show="$deletingId !== null" :title="__('Delete this sequence?')" />
</div>
