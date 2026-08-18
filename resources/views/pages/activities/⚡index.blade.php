<?php

use App\Models\ActivityType;
use App\Services\ActivityTypeService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Activities')] class extends Component
{
    /**
     * A progress chart per activity type, built from its latest sequences.
     *
     * @return list<array{type: string, series: list<array{label: string, values: list<int|float>}>}>
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
                    'series' => [
                        ['label' => __('Weight'), 'values' => $values['weight']],
                        ['label' => __('Repetition'), 'values' => $values['repetition']],
                    ],
                ];
            })
            ->all();
    }
};
?>

<div>
    <x-page-header :title="__('Activities')" />

    <div class="mt-6 space-y-6">
        @forelse ($this->charts as $chart)
            <x-card :header="$chart['type']" wire:key="chart-{{ $loop->index }}">
                <div class="p-4">
                    <x-line-chart :series="$chart['series']" />
                </div>
            </x-card>
        @empty
            <x-empty-state>{{ __('No activity type yet. They are created as you log activities.') }}</x-empty-state>
        @endforelse
    </div>
</div>
