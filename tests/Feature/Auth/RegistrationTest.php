<?php

use App\Models\User;
use App\Services\UserSetupService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
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

it('gives a new user their default activity types and settings', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'Greg')
        ->set('email', 'greg@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $user = User::where('email', 'greg@example.com')->firstOrFail();

    expect($user->activityTypes)->toHaveCount(count(UserSetupService::DEFAULT_ACTIVITY_TYPES))
        ->and($user->settings)->toHaveCount(count(UserSetupService::DEFAULT_SETTINGS));
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
