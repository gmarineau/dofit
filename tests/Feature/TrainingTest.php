<?php

use App\Models\Activity;
use App\Models\Training;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the signed-in user’s trainings, most recent first', function () {
    $older = Training::factory()->for($this->user)->create(['date' => now()->subWeek()]);
    $newer = Training::factory()->for($this->user)->create(['date' => now()]);
    $someoneElses = Training::factory()->create();

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
