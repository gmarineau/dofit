<?php

use App\Models\ActivityType;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\Training;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();

    Exercise::create([
        'slug' => 'barbell-bench-press',
        'name' => 'Barbell Bench Press',
        'category' => 'strength',
        'level' => 'beginner',
        'force' => 'push',
        'mechanic' => 'compound',
        'equipment' => 'barbell',
        'primary_muscles' => ['chest'],
        'secondary_muscles' => ['triceps'],
        'instructions' => ['Lie on the bench.'],
    ]);

    Exercise::create([
        'slug' => 'bodyweight-squat',
        'name' => 'Bodyweight Squat',
        'category' => 'strength',
        'level' => 'beginner',
        'force' => 'push',
        'mechanic' => 'compound',
        'equipment' => 'body only',
        'primary_muscles' => ['quadriceps'],
        'secondary_muscles' => ['glutes'],
        'instructions' => ['Stand tall.'],
    ]);
});

it('searches the library by name', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->set('term', 'bench')
        ->assertSee('Barbell Bench Press')
        ->assertDontSee('Bodyweight Squat');
});

it('filters by muscle, including secondary ones', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->call('filterMuscle', 'triceps')
        ->assertSee('Barbell Bench Press')
        ->assertDontSee('Bodyweight Squat');
});

it('filters by equipment', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->call('filterEquipment', 'body only')
        ->assertSee('Bodyweight Squat')
        ->assertDontSee('Barbell Bench Press');
});

it('clears a filter when it is tapped again', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->call('filterMuscle', 'chest')
        ->assertSet('muscle', 'chest')
        ->call('filterMuscle', 'chest')
        ->assertSet('muscle', null);
});

it('hands the chosen exercise to the parent', function () {
    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->call('choose', $exercise->id)
        ->assertDispatched('exercise-chosen', id: $exercise->id, name: 'Barbell Bench Press');
});

it('hands over a typed name the library does not have', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->set('term', '  Farmer Walk  ')
        ->call('chooseTyped')
        ->assertDispatched('exercise-chosen', id: null, name: 'Farmer Walk');
});

it('ignores an empty typed name', function () {
    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->set('term', '')
        ->call('chooseTyped')
        ->assertNotDispatched('exercise-chosen');
});

it('creates an activity from a library exercise and remembers where it came from', function () {
    $training = Training::factory()->for($this->user)->create();
    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test('pages::activities.create', ['training' => $training])
        ->call('chooseExercise', $exercise->id, $exercise->name);

    $activityType = ActivityType::where('type', 'Barbell Bench Press')->firstOrFail();

    expect($activityType->user_id)->toBe($this->user->id)
        ->and($activityType->exercise_id)->toBe($exercise->id)
        ->and($activityType->exercise->primary_muscles)->toBe(['chest'])
        ->and($training->activities()->count())->toBe(1);
});

it('creates an activity from a name the library does not have', function () {
    $training = Training::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::activities.create', ['training' => $training])
        ->call('chooseExercise', null, 'Farmer Walk');

    expect(ActivityType::where('type', 'Farmer Walk')->firstOrFail()->exercise_id)->toBeNull();
});

it('adds a library exercise to a program with its targets', function () {
    $program = Program::factory()->for($this->user)->create();
    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test('pages::programs.edit', ['program' => $program])
        ->call('chooseExercise', $exercise->id, $exercise->name)
        ->assertSet('type', 'Barbell Bench Press')
        ->set('targetSets', 5)
        ->set('targetReps', 5)
        ->call('addItem')
        ->assertHasNoErrors()
        ->assertSet('type', '');

    $item = $program->items()->firstOrFail();

    expect($item->activityType->exercise_id)->toBe($exercise->id)
        ->and($item->target_formatted)->toBe('5 × 5');
});
