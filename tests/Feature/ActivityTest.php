<?php

use App\Models\Activity;
use App\Models\Exercise;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->training = Training::factory()->for($this->user)->create();
});

it('lists the activities of a training, most recent first', function () {
    $first = Activity::factory()->forTraining($this->training)->create();
    $second = Activity::factory()->forTraining($this->training)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->assertSeeInOrder([$second->exercise->name, $first->exercise->name]);
});

it('creates an activity on an exercise that already exists', function () {
    $existing = Exercise::factory()->create(['name' => 'Bench Press']);

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('addActivity', null, 'Bench Press');

    // The library entry answers for the name; nothing is duplicated.
    expect($this->user->exercises()->count())->toBe(0)
        ->and(Activity::firstOrFail()->exercise_id)->toBe($existing->id);
});

it('adds the exercise to the user’s own the first time a name is used', function () {
    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('addActivity', null, 'Deadlift');

    expect($this->user->exercises()->where('name', 'Deadlift')->exists())->toBeTrue();
});

it('opens and closes the exercise search over the session', function () {
    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->assertSet('picking', false)
        ->call('pick')
        ->assertSet('picking', true)
        ->call('closeModal')
        ->assertSet('picking', false);
});

it('closes the search once an exercise has been added', function () {
    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('pick')
        ->call('addActivity', null, 'Deadlift')
        ->assertSet('picking', false);

    expect($this->training->activities()->count())->toBe(1);
});

it('refuses to add an exercise to someone else’s session', function () {
    $someoneElses = Training::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $someoneElses])
        ->assertForbidden();
});

it('deletes an activity the user owns, along with its sequences', function () {
    $activity = Activity::factory()->forTraining($this->training)->create();
    $sequence = Sequence::factory()->for($activity)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('confirmDelete', $activity->id)
        ->call('delete')
        ->assertSet('deletingId', null);

    expect(Activity::find($activity->id))->toBeNull()
        ->and(Sequence::find($sequence->id))->toBeNull();
});

it('refuses to delete an activity belonging to someone else', function () {
    $someoneElses = Activity::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(Activity::find($someoneElses->id))->not->toBeNull();
});

it('refuses to show an activity belonging to someone else', function () {
    $this->actingAs($this->user)
        ->get(route('activities.show', Activity::factory()->create()))
        ->assertForbidden();
});

it('ticks an activity off from the training page and puts it back', function () {
    $activity = Activity::factory()->forTraining($this->training)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('toggle', $activity->id);

    expect($activity->fresh()->isCompleted())->toBeTrue();

    $component->call('toggle', $activity->id);

    expect($activity->fresh()->isCompleted())->toBeFalse();
});

it('refuses to tick off an activity belonging to someone else', function () {
    $someoneElses = Activity::factory()->forTraining(Training::factory()->create())->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $this->training])
        ->call('toggle', $someoneElses->id)
        ->assertForbidden();

    expect($someoneElses->fresh()->isCompleted())->toBeFalse();
});
