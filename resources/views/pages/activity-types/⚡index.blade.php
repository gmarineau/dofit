<?php

use App\Models\ActivityType;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Activity Types')] class extends Component
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
};
?>

<div>
    <x-page-header :title="__('Activity Types')" />

    <div class="mt-6">
        @if ($this->activityTypes->isEmpty())
            <x-empty-state>{{ __('No activity type yet. They are created as you log activities.') }}</x-empty-state>
        @else
            <x-card>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($this->activityTypes as $activityType)
                        <li wire:key="activity-type-{{ $activityType->id }}" class="flex items-center gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $activityType->type }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $activityType->activities_formatted }}</span>
                            </div>

                            <x-button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="shrink-0 hover:text-danger"
                                wire:click="confirmDelete({{ $activityType->id }})"
                                aria-label="{{ __('Delete activity type') }}"
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
        :title="__('Delete this activity type?')"
        :message="__('Every activity recorded under it will be deleted too.')"
    />
</div>
