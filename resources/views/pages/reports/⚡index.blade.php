<?php

use App\Services\MetricService;
use App\Services\TrainingService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
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

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Reports'));
    }
};
?>

<div>
    <x-page-header :title="__('Reports')" />

    <section class="mb-10">
        <x-section-heading>{{ __('Weight') }}</x-section-heading>

        <x-chart
            :labels="$this->metrics['labels']"
            :datasets="[['label' => __('Weight'), 'data' => $this->metrics['values']]]"
        />
    </section>

    <section class="mb-10">
        <x-section-heading>{{ __('Sessions per month') }}</x-section-heading>

        <x-chart
            :labels="$this->trainings['labels']"
            :datasets="[['label' => __('Sessions'), 'data' => $this->trainings['values']]]"
        />
    </section>
</div>
