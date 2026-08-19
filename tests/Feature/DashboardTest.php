<?php

use App\Models\Activity;
use App\Models\Exercise;
use App\Models\Metric;
use App\Models\Program;
use App\Models\ProgramItem;
use App\Models\ProgramTarget;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Date;
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

/**
 * Log one set on a training of its own, dated as given.
 */
function logSet(User $user, string $date, int $repetition, ?float $weight = null, ?Exercise $exercise = null): void
{
    $training = Training::factory()->for($user)->create(['date' => $date]);

    $activity = Activity::factory()
        ->forTraining($training)
        ->create($exercise !== null ? ['exercise_id' => $exercise->id] : []);

    Sequence::factory()->for($activity)->create([
        'repetition' => $repetition,
        'weight' => $weight,
    ]);
}

it('reports how the volume moved against last month', function () {
    $this->travelTo(Date::parse('2026-08-19 10:00'));

    logSet($this->user, '2026-08-10', 10, 10.0);
    logSet($this->user, '2026-07-10', 10, 5.0);

    expect(app(DashboardService::class)->summary($this->user)['volume_change'])->toBe(100);
});

it('leaves the volume trend empty when last month holds nothing', function () {
    $this->travelTo(Date::parse('2026-08-19 10:00'));

    logSet($this->user, '2026-08-10', 10, 10.0);

    expect(app(DashboardService::class)->summary($this->user)['volume_change'])->toBeNull();
});

it('builds the weekly series in tonnes, oldest week first', function () {
    $this->travelTo(Date::parse('2026-08-19 10:00'));

    logSet($this->user, '2026-08-19', 10, 100.0);

    $series = app(DashboardService::class)->series($this->user, weeks: 2);

    expect($series['labels'])->toBe([
        __('W:week', ['week' => now()->subWeek()->isoWeek()]),
        __('W:week', ['week' => now()->isoWeek()]),
    ])
        ->and($series['volume'])->toBe([0.0, 1.0])
        ->and($series['sessions'])->toBe([0, 1]);
});

it('marks the days trained this week and compares them with last week', function () {
    $this->travelTo(Date::parse('2026-08-19 10:00'));

    logSet($this->user, '2026-08-17', 10, 40.0);
    logSet($this->user, '2026-08-11', 10, 40.0);

    $week = app(DashboardService::class)->currentWeek($this->user);

    expect($week['done'])->toBe(1)
        ->and($week['previous'])->toBe(1)
        ->and(array_column($week['days'], 'done'))->toBe([true, false, false, false, false, false, false]);
});

it('keeps only the heaviest recent set of each exercise', function () {
    $this->travelTo(Date::parse('2026-08-19 10:00'));

    $bench = Exercise::factory()->create(['name' => 'Bench press']);
    $squat = Exercise::factory()->create(['name' => 'Squat']);

    logSet($this->user, '2026-08-18', 5, 92.5, $bench);
    logSet($this->user, '2026-08-11', 8, 80.0, $bench);
    logSet($this->user, '2026-08-12', 3, 130.0, $squat);
    // Too old to still count as recent.
    logSet($this->user, '2026-06-10', 1, 200.0, $squat);

    expect(app(DashboardService::class)->records($this->user))->toBe([
        ['exercise' => 'Squat', 'weight' => 130.0, 'repetition' => 3],
        ['exercise' => 'Bench press', 'weight' => 92.5, 'repetition' => 5],
    ]);
});

it('offers the program behind the most recent program-based session', function () {
    $program = Program::factory()->for($this->user)->create();
    $item = ProgramItem::factory()->for($program)->create();
    ProgramTarget::factory()->for($item)->create(['sets' => 4, 'repetition' => 10]);

    $training = Training::factory()->for($this->user)->create(['date' => now()->subWeek()]);

    Activity::factory()->forTraining($training)->create([
        'exercise_id' => $item->exercise_id,
        'program_item_id' => $item->id,
    ]);

    $session = app(DashboardService::class)->nextSession($this->user);

    expect($session['program']->id)->toBe($program->id)
        ->and($session['exercises'])->toBe(1)
        ->and($session['minutes'])->toBe(12);
});

it('offers no session until one was started from a program', function () {
    Training::factory()->for($this->user)->create();

    expect(app(DashboardService::class)->nextSession($this->user))->toBeNull();
});

it('starts the day’s session from the dashboard', function () {
    $program = Program::factory()->for($this->user)->create();
    $item = ProgramItem::factory()->for($program)->create();
    ProgramTarget::factory()->for($item)->create(['sets' => 2, 'repetition' => 10]);

    $training = Training::factory()->for($this->user)->create(['date' => now()->subWeek()]);

    Activity::factory()->forTraining($training)->create([
        'exercise_id' => $item->exercise_id,
        'program_item_id' => $item->id,
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::dashboard')
        ->assertSee(__('Start the session'))
        ->call('start')
        ->assertRedirect();

    expect($this->user->trainings()->count())->toBe(2);
});

it('hides the start button until there is a session to start', function () {
    Livewire::actingAs($this->user)
        ->test('pages::dashboard')
        ->assertDontSee(__('Start the session'))
        ->assertSee(__('Free session'));
});

it('switches the chart between volume and sessions', function () {
    Livewire::actingAs($this->user)
        ->test('pages::dashboard')
        ->assertSee(__('Last :count weeks · in tonnes', ['count' => DashboardService::CHART_WEEKS]))
        ->set('metric', 'sessions')
        ->assertSee(__('Last :count weeks · sessions', ['count' => DashboardService::CHART_WEEKS]));
});

it('reads the bmi next to the weight curve', function () {
    $this->user->update(['height' => 180]);

    // 75 kg for 1.80 m, a figure no other tile on the page shows.
    Metric::factory()->for($this->user)->create(['value' => '75', 'date' => now()]);

    Livewire::actingAs($this->user->fresh())
        ->test('pages::dashboard')
        ->assertSee('23.1')
        ->assertDontSee(__('Add your height'));
});

it('points at whichever half of the bmi is missing', function () {
    Livewire::actingAs($this->user)
        ->test('pages::dashboard')
        ->assertSee(__('Add your height'));

    $this->user->update(['height' => 180]);

    Livewire::actingAs($this->user->fresh())
        ->test('pages::dashboard')
        ->assertSee(__('Log a weight'));
});
