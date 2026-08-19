<?php

use App\Services\DashboardService;
use App\Services\MetricService;
use App\Services\ProgramService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * Which series the chart draws: the tonnage lifted, or the sessions it
     * took.
     */
    public string $metric = 'volume';

    /**
     * The headline numbers for the current month.
     *
     * @return array{trainings: int, sequences: int, volume: float, volume_change: int|null, weight: float|null, weight_change: float|null}
     */
    #[Computed]
    public function summary(): array
    {
        return app(DashboardService::class)->summary(auth()->user());
    }

    /**
     * The weekly volume and session counts behind the chart.
     *
     * @return array{labels: list<string>, volume: list<float>, sessions: list<int>}
     */
    #[Computed]
    public function series(): array
    {
        return app(DashboardService::class)->series(auth()->user());
    }

    /**
     * The current week, day by day.
     *
     * @return array{days: list<array{letter: string, done: bool}>, done: int, previous: int}
     */
    #[Computed]
    public function week(): array
    {
        return app(DashboardService::class)->currentWeek(auth()->user());
    }

    /**
     * The heaviest sets of the last thirty days.
     *
     * @return list<array{exercise: string, weight: float, repetition: int}>
     */
    #[Computed]
    public function records(): array
    {
        return app(DashboardService::class)->records(auth()->user());
    }

    /**
     * The session the start button launches, when there is one.
     *
     * @return array{program: \App\Models\Program, exercises: int, minutes: int}|null
     */
    #[Computed]
    public function nextSession(): ?array
    {
        return app(DashboardService::class)->nextSession(auth()->user());
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
     * Turn the day's program into a fresh training and open it.
     */
    public function start(ProgramService $programs): void
    {
        $session = $this->nextSession;

        // The button is only rendered when there is a session to start, but
        // the action has to hold that line too.
        if ($session === null) {
            return;
        }

        $this->authorize('view', $session['program']);

        $training = $programs->start($session['program']);

        $this->redirect(route('trainings.show', $training), navigate: true);
    }

    /**
     * Render the page without the layout's card, since it brings its own.
     */
    public function render()
    {
        return $this->view()
            ->title(__('Dashboard'))
            ->layout('layouts::app', ['card' => false]);
    }
};
?>

@php
    $session = $this->nextSession;

    $subtitle = $session !== null
        ? __(':program is waiting for you: :exercises, about :minutes min.', [
            'program' => $session['program']->name,
            'exercises' => trans_choice(':count exercise|:count exercises', $session['exercises'], ['count' => $session['exercises']]),
            'minutes' => $session['minutes'],
        ])
        : __('Nothing scheduled: log a free session, or build a program to start one in a tap.');

    $trend = $this->summary['volume_change'];

    $bmi = auth()->user()->bmi;

    $bmiHealthy = auth()->user()->hasHealthyBmi();

    $delta = $this->week['done'] - $this->week['previous'];

    $weekNote = trans_choice('{0}session done|{1}session done|[2,*]sessions done', $this->week['done']).', '.match (true) {
        $delta > 0 => __(':count more than last week.', ['count' => $delta]),
        $delta < 0 => __(':count fewer than last week.', ['count' => abs($delta)]),
        default => __('the same as last week.'),
    };
@endphp

<div>
    <x-page-header
        :eyebrow="now()->translatedFormat('l j F')"
        :title="__('Hello :name', ['name' => str(auth()->user()->name)->before(' ')])"
        :subtitle="$subtitle"
    >
        <x-slot:actions>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                <x-button
                    :href="route('trainings.create')"
                    as="a"
                    wire:navigate
                    variant="secondary"
                    size="lg"
                    class="order-2 w-full sm:order-1 sm:w-auto"
                >
                    {{ __('Free session') }}
                </x-button>

                @if ($session !== null)
                    <x-button type="button" wire:click="start" size="lg" class="order-1 w-full shadow-accent sm:order-2 sm:w-auto">
                        {{ __('Start the session') }}
                        <x-heroicon-o-arrow-right class="size-[17px]" />
                    </x-button>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- This month at a glance. --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <x-stat icon="o-bolt" :label="__('Sessions')" :value="$this->summary['trainings']" :caption="__('this month')" />

        <x-stat
            icon="o-chart-bar"
            :label="__('Volume')"
            :value="$this->summary['volume'] >= 1000 ? round($this->summary['volume'] / 1000, 1) : $this->summary['volume']"
            :unit="$this->summary['volume'] >= 1000 ? __('t') : __('kg')"
            :caption="$trend !== null
                ? ($trend > 0 ? '+' : '').$trend.' % '.__('vs :month', ['month' => now()->subMonthNoOverflow()->translatedFormat('F')])
                : __('this month')"
            :tone="$trend !== null ? 'warm' : 'neutral'"
        />

        <x-stat icon="o-queue-list" :label="__('Sets')" :value="$this->summary['sequences']" :caption="__('this month')" />

        <x-stat
            icon="o-scale"
            :label="__('Weight')"
            :value="$this->summary['weight'] ?? '—'"
            :unit="$this->summary['weight'] !== null ? __('kg') : null"
            :caption="$this->summary['weight_change'] !== null
                ? ($this->summary['weight_change'] > 0 ? '+' : '').$this->summary['weight_change'].' '.__('kg')
                : null"
            tone="warm"
        />
    </div>

    <div class="grid items-start gap-4 sm:gap-5 lg:grid-cols-[1.65fr_1fr]">
        <x-card
            icon="o-chart-bar"
            :title="__('Volume lifted')"
            :subtitle="$metric === 'volume'
                ? __('Last :count weeks · in tonnes', ['count' => App\Services\DashboardService::CHART_WEEKS])
                : __('Last :count weeks · sessions', ['count' => App\Services\DashboardService::CHART_WEEKS])"
        >
            <x-slot:actions>
                {{-- Same series, two readings: the load, or the turning up. --}}
                <div class="flex shrink-0 gap-0.5 rounded-[10px] bg-raised p-1">
                    @foreach (['volume' => __('Volume'), 'sessions' => __('Sessions')] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('metric', '{{ $key }}')"
                            @class([
                                'rounded-[7px] px-3 py-1.5 text-[12.5px] font-bold transition',
                                'bg-surface text-ink shadow-sm' => $metric === $key,
                                'text-ink-muted hover:text-ink' => $metric !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </x-slot:actions>

            <x-bar-chart
                :labels="$this->series['labels']"
                :values="$metric === 'volume' ? $this->series['volume'] : $this->series['sessions']"
                :decimals="$metric === 'volume' ? 1 : 0"
            />
        </x-card>

        <div class="flex flex-col gap-4 sm:gap-5">
            <x-card icon="o-calendar-days" :title="__('This week')">
                <div class="flex justify-between gap-1.5">
                    @foreach ($this->week['days'] as $day)
                        <div class="flex flex-col items-center gap-2">
                            <span class="text-[11.5px] font-bold text-ink-faint">{{ $day['letter'] }}</span>

                            <span @class([
                                'flex size-8 items-center justify-center rounded-[11px] text-xs font-extrabold',
                                'bg-accent text-accent-ink' => $day['done'],
                                'bg-raised text-ink-faint' => ! $day['done'],
                            ])>
                                @if ($day['done'])
                                    <x-heroicon-m-check class="size-4" />
                                @else
                                    ·
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-baseline gap-[7px] border-t border-line-soft pt-4">
                    <span class="numeric text-[26px] leading-none font-extrabold tracking-[-0.03em] text-ink">
                        {{ $this->week['done'] }}
                    </span>

                    <span class="text-[13.5px] font-semibold text-pretty text-ink-soft">{{ $weekNote }}</span>
                </div>
            </x-card>

            <x-card icon="o-trophy" :title="__('Recent records')" :subtitle="__('Last 30 days')">
                @forelse ($this->records as $record)
                    <div class="flex items-baseline justify-between gap-3 border-t border-line-soft py-3.5">
                        <span class="min-w-0 truncate text-sm font-bold text-ink">{{ $record['exercise'] }}</span>

                        <span class="numeric shrink-0 text-sm font-extrabold whitespace-nowrap text-warm">
                            {{ $record['weight'] }} {{ __('kg') }} × {{ $record['repetition'] }}
                        </span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm font-semibold text-ink-muted">
                        {{ __('No record over the last 30 days yet.') }}
                    </p>
                @endforelse
            </x-card>

            <x-card icon="o-scale" :title="__('Weight')">
                <x-chart
                    :labels="$this->weight['labels']"
                    :datasets="[['label' => __('Weight'), 'data' => $this->weight['values']]]"
                    height="h-40"
                />

                {{-- The curve says where the weight goes, the index says what it means. --}}
                <div class="mt-4 flex items-baseline justify-between gap-3 border-t border-line-soft pt-4">
                    <span class="text-[13.5px] font-semibold text-ink-soft">{{ __('BMI') }}</span>

                    @if ($bmi !== null)
                        {{-- Green inside the healthy band, terracotta outside; red stays destructive. --}}
                        <span @class([
                            'numeric text-[22px] leading-none font-extrabold tracking-[-0.03em]',
                            'text-success' => $bmiHealthy,
                            'text-warm' => ! $bmiHealthy,
                        ])>{{ $bmi }}</span>
                    @else
                        {{-- Point at whichever half is missing. --}}
                        @php($missingHeight = auth()->user()->height === null)

                        <a
                            href="{{ $missingHeight ? route('account.edit') : route('metrics.create') }}"
                            wire:navigate
                            class="text-[13.5px] font-bold text-accent"
                        >
                            {{ $missingHeight ? __('Add your height') : __('Log a weight') }}
                        </a>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>
