<?php

use App\Models\ActivityType;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * A progress chart per activity type, built from its latest sequences.
     *
     * @return list<array{type: string, labels: list<string>, datasets: list<array{label: string, data: list<int|float>}>}>
     */
    #[Computed]
    public function charts(): array
    {
        $activityTypes = app(ActivityTypeService::class);

        return $activityTypes->getUserActivityTypes(auth()->user())
            ->map(function (ActivityType $activityType) use ($activityTypes): array {
                $values = $activityTypes->getValues($activityType);

                return [
                    'type' => $activityType->type,
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
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Activities'));
    }
};
?>

<div>
    <x-page-header :title="__('Activities')" />

    @forelse ($this->charts as $chart)
        <section class="mb-10" wire:key="chart-{{ $loop->index }}">
            <x-section-heading>{{ $chart['type'] }}</x-section-heading>

            <x-chart :datasets="$chart['datasets']" :labels="$chart['labels']" />
        </section>
    @empty
        <x-empty-state icon="o-chart-bar">
            {{ __('No activity type yet. They are created as you log activities.') }}
        </x-empty-state>
    @endforelse
</div>
