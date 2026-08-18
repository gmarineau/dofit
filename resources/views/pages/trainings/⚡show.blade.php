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
    <x-page-header
        :title="$training->name"
        :subtitle="$training->date->translatedFormat('l j F Y')"
        :back="route('trainings.index')"
    >
        <x-slot:actions>
            <x-button :href="route('activities.create', $training)" as="a" wire:navigate class="max-sm:hidden">
                <x-heroicon-o-plus class="size-4" />
                {{ __('New activity') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($this->activities->isNotEmpty())
        <ul>
            @foreach ($this->activities as $activity)
                <li wire:key="activity-{{ $activity->id }}" class="group flex items-center gap-3 border-b border-line last:border-0">
                    <a href="{{ route('activities.show', $activity) }}" wire:navigate class="min-w-0 flex-1 py-4">
                        <div class="truncate font-bold text-ink">{{ $activity->activityType->type }}</div>
                        <div class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $activity->sequences_formatted }}</div>
                    </a>

                    <x-heroicon-o-chevron-right class="size-4 shrink-0 text-ink-muted" />

                    <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                            wire:click="confirmDelete({{ $activity->id }})"
                            aria-label="{{ __('Delete activity') }}"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="o-chart-bar">
            {{ __('No activity in this training yet.') }}

            <x-slot:action>
                <x-button :href="route('activities.create', $training)" as="a" wire:navigate>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('New activity') }}
                </x-button>
            </x-slot:action>
        </x-empty-state>
    @endif

    <x-fab :href="route('activities.create', $training)" wire:navigate :label="__('New activity')" />

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this activity?')"
        :message="__('Its sequences will be deleted too.')"
    />
</div>
