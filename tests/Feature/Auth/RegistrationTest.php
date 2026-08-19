<?php

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

it('registers a new user and logs them in', function () {
    Event::fake();

    Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'greg@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'greg@example.com')->firstOrFail();

    expect(auth()->id())->toBe($user->id);

    Event::assertDispatched(Registered::class);
});

it('gives a new user their default settings', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'greg@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $user = User::where('email', 'greg@example.com')->firstOrFail();

    // Exercises come from the shared library, so only settings are seeded.
    expect($user->settings)->toHaveCount(count(UserSetupService::DEFAULT_SETTINGS))
        ->and($user->exercises)->toBeEmpty();
});

it('rejects an email that is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'taken@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['email' => 'unique']);
});

it('rejects a password that is not confirmed', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'greg@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'something-else')
        ->call('register')
        ->assertHasErrors(['password' => 'confirmed']);
});

/**
 * The password rules only bite in production, so the environment has to be
 * moved for the assertion to mean anything. The breach check is faked: an
 * empty body from the range API means the hash was not found.
 */
function registeringInProduction(string $password): Testable
{
    app()['env'] = 'production';

    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);

    return Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'greg@example.com')
        ->set('password', $password)
        ->set('password_confirmation', $password)
        ->call('register');
}

it('asks production passwords for length rather than punctuation', function () {
    registeringInProduction('correct horse battery staple')->assertHasNoErrors();
});

it('turns away a production password that is merely short', function () {
    registeringInProduction('Sh0rt!x')->assertHasErrors(['password']);
});
