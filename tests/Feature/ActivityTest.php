<?php

use App\Models\Activity;
use App\Models\ActivityType;
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
        ->assertSeeInOrder([$second->activityType->type, $first->activityType->type]);
});

it('creates an activity and reuses an existing activity type', function () {
    $existing = ActivityType::factory()->for($this->user)->create(['type' => 'Bench Press']);

    Livewire::actingAs($this->user)
        ->test('pages::activities.create', ['training' => $this->training])
        ->set('type', 'Bench Press')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->activityTypes()->count())->toBe(1)
        ->and(Activity::firstOrFail()->activity_type_id)->toBe($existing->id);
});

it('creates the activity type the first time a name is used', function () {
    Livewire::actingAs($this->user)
        ->test('pages::activities.create', ['training' => $this->training])
        ->set('type', 'Deadlift')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->activityTypes()->where('type', 'Deadlift')->exists())->toBeTrue();
});

it('requires a type to create an activity', function () {
    Livewire::actingAs($this->user)
        ->test('pages::activities.create', ['training' => $this->training])
        ->set('type', '')
        ->call('save')
        ->assertHasErrors(['type' => 'required']);
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

it('refuses to add an activity to someone else’s training', function () {
    $this->actingAs($this->user)
        ->get(route('activities.create', Training::factory()->create()))
        ->assertForbidden();
});
