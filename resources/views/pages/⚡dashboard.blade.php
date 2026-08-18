<?php

use App\Services\DashboardService;
use App\Services\MetricService;
use App\Services\TrainingService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * The headline numbers for the current month.
     *
     * @return array{trainings: int, sequences: int, volume: float, weight: float|null, weight_change: float|null}
     */
    #[Computed]
    public function summary(): array
    {
        return app(DashboardService::class)->summary(auth()->user());
    }

    /**
     * How many trainings the user recorded per month.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    #[Computed]
    public function sessions(): array
    {
        return app(TrainingService::class)->getSeries(auth()->user());
    }

    /**
     * The user's weight measurements over time.
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    #[Computed]
    public function weight(): array
    {
        return app(MetricService::class)->getSeries(auth()->user());
    }

    /**
     * Render the page with its translated title.
     */
    public function render()
    {
        return $this->view()->title(__('Dashboard'));
    }
};
?>

<div>
    <x-page-header :title="__('Dashboard')" :subtitle="now()->translatedFormat('F Y')" />

    {{-- This month at a glance. --}}
    <div class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-stat :label="__('Sessions')" :value="$this->summary['trainings']" :caption="__('this month')" />

        <x-stat :label="__('Sets')" :value="$this->summary['sequences']" :caption="__('this month')" />

        <x-stat
            :label="__('Volume')"
            :value="$this->summary['volume'] >= 1000 ? round($this->summary['volume'] / 1000, 1) : $this->summary['volume']"
            :unit="$this->summary['volume'] >= 1000 ? __('t') : __('kg')"
            :caption="__('this month')"
        />

        <x-stat
            :label="__('Weight')"
            :value="$this->summary['weight'] ?? '—'"
            :unit="$this->summary['weight'] !== null ? __('kg') : null"
            :caption="$this->summary['weight_change'] !== null
                ? ($this->summary['weight_change'] > 0 ? '+' : '').$this->summary['weight_change'].' '.__('kg')
                : null"
        />
    </div>

    <section class="mb-10">
        <x-section-heading>{{ __('Sessions per month') }}</x-section-heading>

        <x-chart
            :labels="$this->sessions['labels']"
            :datasets="[['label' => __('Sessions'), 'data' => $this->sessions['values']]]"
        />
    </section>

    <section class="mb-10">
        <x-section-heading>{{ __('Weight') }}</x-section-heading>

        <x-chart
            :labels="$this->weight['labels']"
            :datasets="[['label' => __('Weight'), 'data' => $this->weight['values']]]"
        />
    </section>
</div>
