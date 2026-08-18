<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('logs a user in with the right credentials', function () {
    $user = User::factory()->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('requires an email and a password', function () {
    Livewire::test('pages::auth.login')
        ->call('login')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});

it('throttles the login after five failed attempts', function () {
    $user = User::factory()->create();

    $component = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password');

    foreach (range(1, 5) as $attempt) {
        $component->call('login')->assertHasErrors('email');
    }

    $component->call('login');

    // The message switches from "wrong credentials" to the throttle notice,
    // whichever locale the application runs in.
    expect($component->errors()->first('email'))->not->toBe(__('auth.failed'));

    RateLimiter::clear(Str::lower($user->email).'|'.request()->ip());
});

it('logs a user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('keeps authenticated users away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect('/');
});
