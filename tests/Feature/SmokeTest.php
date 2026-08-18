<?php

use App\Models\Activity;
use App\Models\Sequence;
use App\Models\Setting;
use App\Models\Training;
use App\Models\User;
use App\Services\UserSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();

    app(UserSetupService::class)->setUp($this->user);

    $this->training = Training::factory()->for($this->user)->create();
    $this->activity = Activity::factory()->forTraining($this->training)->create();

    Sequence::factory()->for($this->activity)->create();
});

it('renders every authenticated page', function (string $route) {
    $this->actingAs($this->user)
        ->get($route)
        ->assertOk();
})->with(fn () => [
    '/',
    '/trainings',
    '/trainings/create',
    '/programs',
    '/programs/create',
    '/metrics',
    '/metrics/create',
    '/exercises',
    '/exercises/create',
    '/account',
    '/account/edit',
]);

it('renders every page that takes a model', function () {
    $this->actingAs($this->user);

    $this->get(route('trainings.show', $this->training))->assertOk();
    $this->get(route('activities.show', $this->activity))->assertOk();
    $this->get(route('sequences.create', $this->activity))->assertOk();
    $this->get(route('settings.edit', Setting::where('user_id', $this->user->id)->first()))->assertOk();
});

it('renders every guest page', function (string $route) {
    $this->get($route)->assertOk();
})->with([
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password/some-token',
]);

it('redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('gives every page a translated browser title', function () {
    $this->actingAs($this->user)
        ->get('/')
        ->assertSee('<title>'.__('Dashboard').' — '.config('app.name').'</title>', escape: false);

    $this->get(route('trainings.index'))
        ->assertSee('<title>'.__('Trainings').' — '.config('app.name').'</title>', escape: false);

    $this->get(route('trainings.show', $this->training))
        ->assertSee('<title>'.$this->training->name.' — '.config('app.name').'</title>', escape: false);
});
