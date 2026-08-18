<?php

use App\Services\MetricService;
use App\Services\TrainingService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component
{
    /**
     * The user's weight measurements over time.
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    #[Computed]
    public function metrics(): array
    {
        return app(MetricService::class)->getSeries(auth()->user());
    }

    /**
     * The number of trainings the user recorded per month.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    #[Computed]
    public function trainings(): array
    {
        return app(TrainingService::class)->getSeries(auth()->user());
    }
};
?>

<div>
    <x-page-header :title="__('Reports')" />

    <div class="mt-6 space-y-6">
        <x-card :header="__('Metrics')">
            <div class="p-4">
                <x-line-chart
                    :labels="$this->metrics['labels']"
                    :series="[['label' => __('Weight'), 'values' => $this->metrics['values']]]"
                />
            </div>
        </x-card>

        <x-card :header="__('Trainings')">
            <div class="p-4">
                <x-line-chart
                    :labels="$this->trainings['labels']"
                    :series="[['label' => __('Trainings'), 'values' => $this->trainings['values']]]"
                />
            </div>
        </x-card>
    </div>
</div>
