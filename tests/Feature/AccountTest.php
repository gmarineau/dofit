<?php

use App\Models\Metric;
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

it('records the height and reads the bmi off the latest weight', function () {
    $this->user->update(['height' => 180]);

    Metric::factory()->for($this->user)->create(['value' => '90', 'date' => now()->subMonth()]);
    Metric::factory()->for($this->user)->create(['value' => '81', 'date' => now()]);

    // 81 kg for 1.80 m.
    expect($this->user->fresh()->bmi)->toBe(25.0);

    Livewire::actingAs($this->user->fresh())
        ->test('pages::account.index')
        ->assertSee('180')
        ->assertSee('25');
});

it('leaves the bmi empty until both the height and a weight are known', function () {
    expect($this->user->bmi)->toBeNull();

    $this->user->update(['height' => 180]);

    // A height on its own is not enough.
    expect($this->user->fresh()->bmi)->toBeNull();

    Metric::factory()->for($this->user)->create(['value' => '81', 'date' => now()]);

    expect($this->user->fresh()->bmi)->toBe(25.0);
});

it('warms up the bmi once it leaves the healthy band', function () {
    $this->user->update(['height' => 180]);

    // 75 kg for 1.80 m sits inside the band, 95 kg does not.
    $healthy = Metric::factory()->for($this->user)->create(['value' => '75', 'date' => now()]);

    expect($this->user->fresh()->hasHealthyBmi())->toBeTrue();

    Livewire::actingAs($this->user->fresh())
        ->test('pages::account.index')
        ->assertSeeHtml('text-success')
        ->assertDontSeeHtml('text-warm');

    $healthy->update(['value' => '95']);

    expect($this->user->fresh()->hasHealthyBmi())->toBeFalse();

    Livewire::actingAs($this->user->fresh())
        ->test('pages::account.index')
        ->assertSeeHtml('text-warm')
        ->assertDontSeeHtml('text-success');
});

it('says nothing about a bmi it cannot work out', function () {
    expect($this->user->hasHealthyBmi())->toBeNull();

    Livewire::actingAs($this->user)
        ->test('pages::account.index')
        ->assertDontSeeHtml('text-warm')
        ->assertDontSeeHtml('text-success');
});

it('updates the height from the edit form', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->assertSet('height', null)
        ->set('height', 178)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->fresh()->height)->toBe(178);
});

it('rejects a height that cannot be a person', function () {
    Livewire::actingAs($this->user)
        ->test('pages::account.edit')
        ->set('height', 1780)
        ->call('save')
        ->assertHasErrors(['height']);

    expect($this->user->fresh()->height)->toBeNull();
});

it('accepts an empty height', function () {
    $this->user->update(['height' => 178]);

    Livewire::actingAs($this->user->fresh())
        ->test('pages::account.edit')
        ->set('height', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->fresh()->height)->toBeNull();
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
