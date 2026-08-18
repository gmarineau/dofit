<?php

use App\Models\Activity;
use App\Models\Training;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the signed-in user’s trainings, most recent first', function () {
    // Explicit names: Faker's single words collide with each other and with
    // the page's own wording often enough to make the order assertion flaky.
    $older = Training::factory()->for($this->user)->create(['date' => now()->subWeek(), 'name' => 'Older session']);
    $newer = Training::factory()->for($this->user)->create(['date' => now(), 'name' => 'Newer session']);
    $someoneElses = Training::factory()->create(['name' => 'Hidden session']);

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->assertSeeInOrder([$newer->name, $older->name])
        ->assertDontSee($someoneElses->name);
});

it('creates a training and opens it', function () {
    Livewire::actingAs($this->user)
        ->test('pages::trainings.create')
        ->set('name', 'Leg day')
        ->set('date', '2026-03-01')
        ->call('save')
        ->assertHasNoErrors();

    $training = Training::where('name', 'Leg day')->firstOrFail();

    expect($training->user_id)->toBe($this->user->id)
        ->and($training->date->format('Y-m-d'))->toBe('2026-03-01');
});

it('requires a name and a date to create a training', function () {
    Livewire::actingAs($this->user)
        ->test('pages::trainings.create')
        ->set('name', '')
        ->set('date', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'date' => 'required']);
});

it('deletes a training the user owns', function () {
    $training = Training::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->call('confirmDelete', $training->id)
        ->assertSet('deletingId', $training->id)
        ->call('delete')
        ->assertSet('deletingId', null);

    expect(Training::find($training->id))->toBeNull();
});

it('cancels a pending training deletion', function () {
    $training = Training::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->call('confirmDelete', $training->id)
        ->call('cancelDelete')
        ->assertSet('deletingId', null);

    expect(Training::find($training->id))->not->toBeNull();
});

it('refuses to delete a training belonging to someone else', function () {
    $someoneElses = Training::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(Training::find($someoneElses->id))->not->toBeNull();
});

it('refuses to show a training belonging to someone else', function () {
    $someoneElses = Training::factory()->create();

    $this->actingAs($this->user)
        ->get(route('trainings.show', $someoneElses))
        ->assertForbidden();
});

it('deletes the training’s activities along with it', function () {
    $training = Training::factory()->for($this->user)->create();
    $activity = Activity::factory()->forTraining($training)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->call('confirmDelete', $training->id)
        ->call('delete');

    expect(Activity::find($activity->id))->toBeNull();
});

it('finishes a session, counting whatever is left as done', function () {
    $training = Training::factory()->for($this->user)->create();
    $done = Activity::factory()->forTraining($training)->create(['completed_at' => now()->subHour()]);
    $left = Activity::factory()->forTraining($training)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('complete');

    expect($training->fresh()->isCompleted())->toBeTrue()
        ->and($left->fresh()->isCompleted())->toBeTrue()
        ->and($done->fresh()->completed_at->isBefore(now()->subMinutes(30)))->toBeTrue();
});

it('refuses to finish a training that holds no activity', function () {
    $training = Training::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('complete');

    expect($training->fresh()->isCompleted())->toBeFalse();
});

it('reopens a finished session, leaving its activities ticked off', function () {
    $training = Training::factory()->for($this->user)->create(['completed_at' => now()]);
    $activity = Activity::factory()->forTraining($training)->create(['completed_at' => now()]);

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('reopen');

    expect($training->fresh()->isCompleted())->toBeFalse()
        ->and($activity->fresh()->isCompleted())->toBeTrue();
});

it('reports how far through the session the user is', function () {
    $training = Training::factory()->for($this->user)->create();
    Activity::factory()->forTraining($training)->create(['completed_at' => now()]);
    Activity::factory()->forTraining($training)->count(2)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.index')
        ->assertSee('1/3');
});

it('fills the progress bar as the activities are ticked off', function () {
    $training = Training::factory()->for($this->user)->create();
    Activity::factory()->forTraining($training)->create(['completed_at' => now()]);
    $activity = Activity::factory()->forTraining($training)->create();
    Activity::factory()->forTraining($training)->create();

    // The bar only shows while the session is open, and the last tick closes it.
    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->assertSee('width: 33%', escape: false)
        ->call('toggle', $activity->id)
        ->assertSee('width: 67%', escape: false);
});

it('closes the session on its own once every activity is ticked off', function () {
    $training = Training::factory()->for($this->user)->create();
    $first = Activity::factory()->forTraining($training)->create();
    $second = Activity::factory()->forTraining($training)->create();

    $component = Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('toggle', $first->id);

    expect($training->fresh()->isCompleted())->toBeFalse();

    $component->call('toggle', $second->id);

    expect($training->fresh()->isCompleted())->toBeTrue();
});

it('reopens the session when an activity goes back on the to-do list', function () {
    $training = Training::factory()->for($this->user)->create(['completed_at' => now()]);
    $activity = Activity::factory()->forTraining($training)->create(['completed_at' => now()]);

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('toggle', $activity->id);

    expect($training->fresh()->isCompleted())->toBeFalse();
});

it('reopens the session when an activity is added to it', function () {
    $training = Training::factory()->for($this->user)->create(['completed_at' => now()]);
    Activity::factory()->forTraining($training)->create(['completed_at' => now()]);

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('addActivity', null, 'Squat');

    expect($training->fresh()->isCompleted())->toBeFalse();
});

it('closes the session when the last unfinished activity is deleted', function () {
    $training = Training::factory()->for($this->user)->create();
    Activity::factory()->forTraining($training)->create(['completed_at' => now()]);
    $unfinished = Activity::factory()->forTraining($training)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('confirmDelete', $unfinished->id)
        ->call('delete');

    expect($training->fresh()->isCompleted())->toBeTrue();
});

it('leaves an emptied session open', function () {
    $training = Training::factory()->for($this->user)->create();
    $only = Activity::factory()->forTraining($training)->create();

    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('confirmDelete', $only->id)
        ->call('delete');

    expect($training->fresh()->isCompleted())->toBeFalse();
});
