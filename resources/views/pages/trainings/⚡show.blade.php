<?php

use App\Models\Activity;
use App\Models\Training;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Training $training;

    public ?int $deletingId = null;

    /**
     * Load the training the route points at.
     */
    public function mount(Training $training): void
    {
        $this->authorize('view', $training);

        $this->training = $training;
    }

    /**
     * Show the training's name in the browser tab.
     */
    public function title(): string
    {
        return (string) $this->training->name;
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
            ->with('activityType')
            ->withCount('sequences')
            ->orderByDesc('id')
            ->get();
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
    public function delete(): void
    {
        $activity = Activity::findOrFail($this->deletingId);

        $this->authorize('delete', $activity);

        $activity->delete();

        $this->deletingId = null;

        unset($this->activities);
    }
};
?>

<div>
    <x-page-header :title="$training->name" :back="route('dashboard')">
        <x-slot:actions>
            <x-button :href="route('activities.create', $training)" as="a" size="icon" wire:navigate aria-label="{{ __('New activity') }}">
                <x-icons.plus />
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        {{ $training->date->format(config('dofit.date_format')) }}
    </p>

    <div class="mt-6">
        @if ($this->activities->isEmpty())
            <x-empty-state>{{ __('No activity in this training yet.') }}</x-empty-state>
        @else
            <x-card>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($this->activities as $activity)
                        <li wire:key="activity-{{ $activity->id }}" class="flex items-center gap-3 px-4 py-3">
                            <a href="{{ route('activities.show', $activity) }}" wire:navigate class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $activity->activityType->type }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $activity->sequences_formatted }}</span>
                            </a>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="shrink-0 hover:text-danger"
                                wire:click="confirmDelete({{ $activity->id }})"
                                aria-label="{{ __('Delete activity') }}"
                            >
                                <x-icons.x class="size-4" />
                            </x-button>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this activity?')"
        :message="__('Its sequences will be deleted too.')"
    />
</div>
