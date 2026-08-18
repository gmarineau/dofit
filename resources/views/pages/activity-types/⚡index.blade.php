<?php

use App\Models\ActivityType;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $deletingId = null;

    /**
     * The activity types the user has created, with their activity counts.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ActivityType>
     */
    #[Computed]
    public function activityTypes()
    {
        return app(ActivityTypeService::class)->getUserActivityTypes(auth()->user());
    }

    /**
     * Ask the user to confirm deleting an activity type.
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
     * Delete the activity type the user confirmed.
     */
    public function delete(): void
    {
        $activityType = ActivityType::findOrFail($this->deletingId);

        $this->authorize('delete', $activityType);

        $activityType->delete();

        $this->deletingId = null;

        unset($this->activityTypes);
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Activity Types'));
    }
};
?>

<div>
    <x-page-header :title="__('Activity Types')" />

    @if ($this->activityTypes->isNotEmpty())
        <ul>
            @foreach ($this->activityTypes as $activityType)
                <li wire:key="activity-type-{{ $activityType->id }}" class="group flex items-center gap-3 border-b border-line py-4 last:border-0">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-bold text-ink">{{ $activityType->type }}</div>
                        <div class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $activityType->activities_formatted }}</div>
                    </div>

                    <x-button
                            type="button"
                            variant="quiet-danger"
                            size="icon-sm"
                            class="opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 max-sm:opacity-100"
                            wire:click="confirmDelete({{ $activityType->id }})"
                            aria-label="{{ __('Delete activity type') }}"
                        >
                            <x-heroicon-o-x-mark class="size-4" />
                        </x-button>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="o-list-bullet">
            {{ __('No activity type yet. They are created as you log activities.') }}
        </x-empty-state>
    @endif

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this activity type?')"
        :message="__('Every activity recorded under it will be deleted too.')"
    />
</div>
