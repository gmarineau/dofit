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

it('changes the language the interface is read in', function () {
    $user = User::factory()->create(['locale' => null]);

    Livewire::actingAs($user)
        ->test('pages::account.edit')
        ->assertSet('locale', config('app.locale'))
        ->set('locale', 'en')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->locale)->toBe('en');
});

it('refuses a language the app does not ship', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::account.edit')
        ->set('locale', 'kl')
        ->call('save')
        ->assertHasErrors(['locale']);

    expect($user->fresh()->locale)->not->toBe('kl');
});

it('renders the interface in the user’s language', function () {
    $english = User::factory()->create(['locale' => 'en']);

    $this->actingAs($english)->get(route('trainings.index'))->assertSee('Trainings');

    $french = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($french)->get(route('trainings.index'))->assertSee('Entraînements');
});

it('falls back to the application language for a user without one', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get(route('trainings.index'));

    expect(app()->getLocale())->toBe(config('app.locale'));
});
