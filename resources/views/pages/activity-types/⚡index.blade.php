<?php

use App\Models\ActivityType;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $deletingId = null;

    /**
     * Every activity type the user owns, each with the progression of its
     * latest sequences.
     *
     * @return list<array{
     *     type: ActivityType,
     *     labels: list<string>,
     *     datasets: list<array{label: string, data: list<int|float>}>,
     * }>
     */
    #[Computed]
    public function activityTypes(): array
    {
        $service = app(ActivityTypeService::class);

        return $service->getUserActivityTypes(auth()->user())
            ->map(function (ActivityType $activityType) use ($service): array {
                $values = $service->getValues($activityType);

                return [
                    'type' => $activityType,
                    // Sequences have no meaningful date, so they are numbered.
                    'labels' => array_map(
                        fn (int $index): string => (string) ($index + 1),
                        array_keys($values['weight']),
                    ),
                    'datasets' => [
                        ['label' => __('Weight'), 'data' => $values['weight']],
                        ['label' => __('Repetition'), 'data' => $values['repetition']],
                    ],
                ];
            })
            ->all();
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
    <x-page-header :title="__('Activity Types')" :subtitle="__('Your exercises and how they progress.')" :back="route('account')" />

    @forelse ($this->activityTypes as $entry)
        <section class="mb-10" wire:key="type-{{ $entry['type']->id }}">
            <div class="mb-2 flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="truncate font-bold text-ink">{{ $entry['type']->type }}</h2>
                    <p class="mt-0.5 text-sm font-semibold text-ink-soft">{{ $entry['type']->activities_formatted }}</p>
                </div>

                <x-button
                    type="button"
                    variant="quiet-danger"
                    size="icon-sm"
                    wire:click="confirmDelete({{ $entry['type']->id }})"
                    aria-label="{{ __('Delete activity type') }}"
                >
                    <x-heroicon-o-x-mark class="size-4" />
                </x-button>
            </div>

            <x-chart :labels="$entry['labels']" :datasets="$entry['datasets']" height="h-40" />
        </section>
    @empty
        <x-empty-state icon="o-list-bullet">
            {{ __('No activity type yet. They are created as you log activities.') }}
        </x-empty-state>
    @endforelse

    <x-confirm-delete
        :show="$deletingId !== null"
        :title="__('Delete this activity type?')"
        :message="__('Every activity recorded under it will be deleted too.')"
    />
</div>
