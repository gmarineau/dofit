<?php

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Training;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the signed-in user’s activity types with their activity counts', function () {
    $activityType = ActivityType::factory()->for($this->user)->create(['type' => 'Bench Press']);
    $someoneElses = ActivityType::factory()->create(['type' => 'Hidden Type']);

    $training = Training::factory()->for($this->user)->create();
    Activity::factory()->count(2)->create([
        'training_id' => $training->id,
        'activity_type_id' => $activityType->id,
    ]);

    Livewire::actingAs($this->user)
        ->test('pages::activity-types.index')
        ->assertSee('Bench Press')
        ->assertSee('2 activities')
        ->assertDontSee($someoneElses->type);
});

it('deletes an activity type the user owns', function () {
    $activityType = ActivityType::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::activity-types.index')
        ->call('confirmDelete', $activityType->id)
        ->call('delete');

    expect(ActivityType::find($activityType->id))->toBeNull();
});

it('refuses to delete an activity type belonging to someone else', function () {
    $someoneElses = ActivityType::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::activity-types.index')
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(ActivityType::find($someoneElses->id))->not->toBeNull();
});
