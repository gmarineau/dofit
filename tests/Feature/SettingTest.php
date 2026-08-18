<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->setting = Setting::factory()->for($this->user)->create([
        'key' => 'repetition',
        'value' => '10',
        'type' => 'number',
    ]);
});

it('prefills the form with the current value', function () {
    Livewire::actingAs($this->user)
        ->test('pages::settings.edit', ['setting' => $this->setting])
        ->assertSet('value', '10');
});

it('updates a setting the user owns', function () {
    Livewire::actingAs($this->user)
        ->test('pages::settings.edit', ['setting' => $this->setting])
        ->set('value', '12')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('account'));

    expect($this->setting->fresh()->value)->toBe('12');
});

it('requires a value', function () {
    Livewire::actingAs($this->user)
        ->test('pages::settings.edit', ['setting' => $this->setting])
        ->set('value', '')
        ->call('save')
        ->assertHasErrors(['value' => 'required']);
});

it('refuses to edit a setting belonging to someone else', function () {
    $someoneElses = Setting::factory()->create();

    $this->actingAs($this->user)
        ->get(route('settings.edit', $someoneElses))
        ->assertForbidden();
});
