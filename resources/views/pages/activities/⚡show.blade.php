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

        $this->activity = $activity->load('activityType', 'training');
    }

    /**
     * Render the page, showing the activity type in the browser tab.
     */
    public function render()
    {
        return $this->view()->title($this->activity->activityType->type);
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
        :title="$activity->activityType->type"
        :subtitle="$activity->training->date->translatedFormat('j F Y')"
        :back="route('trainings.show', $activity->training)"
    >
        <x-slot:actions>
            <x-button :href="route('sequences.create', $activity)" as="a" wire:navigate class="max-sm:hidden">
                <x-heroicon-o-plus class="size-4" />
                {{ __('New sequence') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->sequences->isNotEmpty())
        <ul>
            @foreach ($this->sequences as $sequence)
                {{-- The load is the point of the row, so it gets the size. --}}
                <li wire:key="sequence-{{ $sequence->id }}" class="group flex items-center gap-4 border-b border-line py-4 last:border-0">
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
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                            wire:click="confirmDelete({{ $sequence->id }})"
                            aria-label="{{ __('Delete sequence') }}"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>
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

    <x-fab :href="route('sequences.create', $activity)" wire:navigate :label="__('New sequence')" />

    <x-confirm-delete :show="$deletingId !== null" :title="__('Delete this sequence?')" />
</div>
