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
     * Show the activity type in the browser tab.
     */
    public function title(): string
    {
        return $this->activity->activityType->type;
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
    <x-page-header :title="$activity->activityType->type" :back="route('trainings.show', $activity->training)">
        <x-slot:actions>
            <x-button :href="route('sequences.create', $activity)" as="a" size="icon" wire:navigate aria-label="{{ __('New sequence') }}">
                <x-icons.plus />
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        @if ($this->sequences->isEmpty())
            <x-empty-state>{{ __('No sequence recorded yet.') }}</x-empty-state>
        @else
            <x-card>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($this->sequences as $sequence)
                        <li wire:key="sequence-{{ $sequence->id }}" class="flex items-center gap-3 px-4 py-3">
                            <span class="min-w-0 flex-1 font-medium tabular-nums">
                                {{ $sequence->value }}
                                @if ($sequence->weight !== null)
                                    <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ $sequence->unit->label() }}</span>
                                @endif
                            </span>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="shrink-0 hover:text-danger"
                                wire:click="confirmDelete({{ $sequence->id }})"
                                aria-label="{{ __('Delete sequence') }}"
                            >
                                <x-icons.x class="size-4" />
                            </x-button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    <x-confirm-delete :show="$deletingId !== null" :title="__('Delete this sequence?')" />
</div>
