<?php

use App\Models\Activity;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use App\Services\UserSetupService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();

    app(UserSetupService::class)->setUp($this->user);

    $this->training = Training::factory()->for($this->user)->create();
    $this->activity = Activity::factory()->forTraining($this->training)->create();
});

it('lists the sequences of an activity in the order performed', function () {
    Sequence::factory()->for($this->activity)->create(['repetition' => 8, 'weight' => 40]);
    Sequence::factory()->for($this->activity)->create(['repetition' => 6, 'weight' => 45]);

    Livewire::actingAs($this->user)
        ->test('pages::activities.show', ['activity' => $this->activity])
        ->assertSeeInOrder(['40.0', '45.0']);
});

it('prefills the form with the user’s default repetition count', function () {
    Livewire::actingAs($this->user)
        ->test('pages::sequences.create', ['activity' => $this->activity])
        ->assertSet('repetition', 10);
});

it('prefills the weight from the activity’s last sequence', function () {
    Sequence::factory()->for($this->activity)->create(['weight' => 40]);
    Sequence::factory()->for($this->activity)->create(['weight' => 47.5]);

    Livewire::actingAs($this->user)
        ->test('pages::sequences.create', ['activity' => $this->activity])
        ->assertSet('weight', 47.5);
});

it('records a sequence', function () {
    Livewire::actingAs($this->user)
        ->test('pages::sequences.create', ['activity' => $this->activity])
        ->set('repetition', 12)
        ->set('weight', 30.5)
        ->call('save')
        ->assertHasNoErrors();

    $sequence = Sequence::firstOrFail();

    expect($sequence->activity_id)->toBe($this->activity->id)
        ->and($sequence->repetition)->toBe(12)
        ->and($sequence->weight)->toBe(30.5);
});

it('records a sequence without any weight', function () {
    Livewire::actingAs($this->user)
        ->test('pages::sequences.create', ['activity' => $this->activity])
        ->set('repetition', 15)
        ->set('weight', null)
        ->call('save')
        ->assertHasNoErrors();

    expect(Sequence::firstOrFail()->value)->toBe('15');
});

it('requires a repetition count', function () {
    Livewire::actingAs($this->user)
        ->test('pages::sequences.create', ['activity' => $this->activity])
        ->set('repetition', null)
        ->call('save')
        ->assertHasErrors(['repetition' => 'required']);
});

it('deletes a sequence the user owns', function () {
    $sequence = Sequence::factory()->for($this->activity)->create();

    Livewire::actingAs($this->user)
        ->test('pages::activities.show', ['activity' => $this->activity])
        ->call('confirmDelete', $sequence->id)
        ->call('delete');

    expect(Sequence::find($sequence->id))->toBeNull();
});

it('refuses to delete a sequence belonging to someone else', function () {
    $someoneElses = Sequence::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::activities.show', ['activity' => $this->activity])
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(Sequence::find($someoneElses->id))->not->toBeNull();
});

it('refuses to add a sequence to someone else’s activity', function () {
    $this->actingAs($this->user)
        ->get(route('sequences.create', Activity::factory()->create()))
        ->assertForbidden();
});
