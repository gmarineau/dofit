<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Greg', 'birthdate' => '1985-04-12']);
});

it('shows the signed-in user’s details', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.index')
        ->assertSee('Greg')
        ->assertSee($this->user->email)
        ->assertSee('12.04.1985');
});

it('prefills the edit form with the current details', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->assertSet('name', 'Greg')
        ->assertSet('email', $this->user->email)
        ->assertSet('birthdate', '1985-04-12');
});

it('updates the account details', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->set('name', 'Gregory')
        ->set('email', 'gregory@example.com')
        ->set('birthdate', '1990-01-01')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('account'));

    $this->user->refresh();

    expect($this->user->name)->toBe('Gregory')
        ->and($this->user->email)->toBe('gregory@example.com')
        ->and($this->user->birthdate->format('Y-m-d'))->toBe('1990-01-01');
});

it('lets the user keep their own email address', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->set('name', 'Gregory')
        ->call('save')
        ->assertHasNoErrors();
});

it('rejects an email already used by someone else', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

it('accepts an empty birthdate', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->set('birthdate', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->fresh()->birthdate)->toBeNull();
});
