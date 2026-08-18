<?php

use App\Models\Activity;
use App\Models\Metric;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use App\Services\DashboardService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('counts only this month’s sessions and sets', function () {
    $thisMonth = Training::factory()->for($this->user)->create(['date' => now()]);
    $lastMonth = Training::factory()->for($this->user)->create(['date' => now()->subMonthNoOverflow()]);

    Sequence::factory()
        ->count(3)
        ->for(Activity::factory()->forTraining($thisMonth))
        ->create(['repetition' => 10, 'weight' => 20]);

    Sequence::factory()
        ->count(5)
        ->for(Activity::factory()->forTraining($lastMonth))
        ->create(['repetition' => 10, 'weight' => 20]);

    $summary = app(DashboardService::class)->summary($this->user);

    expect($summary['trainings'])->toBe(1)
        ->and($summary['sequences'])->toBe(3)
        ->and($summary['volume'])->toBe(600.0);
});

it('ignores other users’ trainings', function () {
    Training::factory()->create(['date' => now()]);

    expect(app(DashboardService::class)->summary($this->user)['trainings'])->toBe(0);
});

it('treats a bodyweight set as no added volume', function () {
    $training = Training::factory()->for($this->user)->create(['date' => now()]);

    Sequence::factory()
        ->bodyweight()
        ->for(Activity::factory()->forTraining($training))
        ->create(['repetition' => 12]);

    $summary = app(DashboardService::class)->summary($this->user);

    expect($summary['sequences'])->toBe(1)
        ->and($summary['volume'])->toBe(0.0);
});

it('reports the latest weight and how it moved', function () {
    Metric::factory()->for($this->user)->create(['value' => '80.0', 'date' => now()->subWeek()]);
    Metric::factory()->for($this->user)->create(['value' => '78.5', 'date' => now()]);

    $summary = app(DashboardService::class)->summary($this->user);

    expect($summary['weight'])->toBe(78.5)
        ->and($summary['weight_change'])->toBe(-1.5);
});

it('leaves the weight empty when nothing was measured', function () {
    $summary = app(DashboardService::class)->summary($this->user);

    expect($summary['weight'])->toBeNull()
        ->and($summary['weight_change'])->toBeNull();
});

it('shows the summary on the dashboard', function () {
    Training::factory()->count(2)->for($this->user)->create(['date' => now()]);

    Livewire::actingAs($this->user)
        ->test('pages::dashboard')
        ->assertSee(__('Sessions'))
        ->assertSee(__('Volume'))
        ->assertSet('summary.trainings', 2);
});
