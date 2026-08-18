<?php

use App\Models\Metric;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the signed-in user’s metrics, most recent first', function () {
    $older = Metric::factory()->for($this->user)->create(['value' => '80.0', 'date' => now()->subWeek()]);
    $newer = Metric::factory()->for($this->user)->create(['value' => '78.5', 'date' => now()]);
    Metric::factory()->create(['value' => '99.9']);

    Livewire::actingAs($this->user)
        ->test('pages::metrics.index')
        ->assertSeeInOrder([$newer->value_formatted, $older->value_formatted])
        ->assertDontSee('99.9');
});

it('records a weight measurement', function () {
    Livewire::actingAs($this->user)
        ->test('pages::metrics.create')
        ->set('value', 76.4)
        ->call('save')
        ->assertHasNoErrors();

    $metric = Metric::firstOrFail();

    expect($metric->user_id)->toBe($this->user->id)
        ->and($metric->key)->toBe('weight')
        ->and((float) $metric->value)->toBe(76.4);
});

it('requires a numeric value', function () {
    Livewire::actingAs($this->user)
        ->test('pages::metrics.create')
        ->set('value', null)
        ->call('save')
        ->assertHasErrors(['value' => 'required']);
});

it('deletes a metric the user owns', function () {
    $metric = Metric::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::metrics.index')
        ->call('confirmDelete', $metric->id)
        ->call('delete');

    expect(Metric::find($metric->id))->toBeNull();
});

it('refuses to delete a metric belonging to someone else', function () {
    $someoneElses = Metric::factory()->create();

    Livewire::actingAs($this->user)
        ->test('pages::metrics.index')
        ->call('confirmDelete', $someoneElses->id)
        ->call('delete')
        ->assertForbidden();

    expect(Metric::find($someoneElses->id))->not->toBeNull();
});
