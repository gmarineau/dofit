<?php

use App\Models\Activity;
use App\Models\Exercise;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use App\Services\ExerciseService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Meilisearch\Exceptions\CommunicationException;

beforeEach(function () {
    $this->user = User::factory()->create();
});

/**
 * Build an entry of the shared library.
 *
 * @param  array<string, mixed>  $attributes
 */
function libraryExercise(array $attributes = []): Exercise
{
    return Exercise::factory()->create([
        'name' => 'Bench Press',
        'slug' => fake()->unique()->slug(),
        'secondary_muscles' => ['triceps'],
        'instructions' => ['Lie on the bench.', 'Press the bar up.'],
        ...$attributes,
    ]);
}

it('lists the library and filters it by name', function () {
    libraryExercise(['name' => 'Bench Press']);
    libraryExercise(['name' => 'Deadlift']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->assertSee('Bench Press')
        ->assertSee('Deadlift')
        ->set('term', 'Bench')
        ->assertSee('Bench Press')
        ->assertDontSee('Deadlift');
});

it('filters the library by muscle and by equipment', function () {
    libraryExercise(['name' => 'Bench Press', 'primary_muscles' => ['chest'], 'equipment' => 'barbell']);
    libraryExercise(['name' => 'Leg Curl', 'primary_muscles' => ['hamstrings'], 'equipment' => 'machine']);

    $component = Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterMuscle', 'chest')
        ->assertSee('Bench Press')
        ->assertDontSee('Leg Curl');

    // Tapping the same filter again clears it.
    $component->call('filterMuscle', 'chest')
        ->call('filterEquipment', 'machine')
        ->assertSee('Leg Curl')
        ->assertDontSee('Bench Press');
});

it('pins and unpins an exercise', function () {
    $exercise = libraryExercise();

    $component = Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('toggleFavorite', $exercise->id);

    expect($this->user->favoriteExercises()->count())->toBe(1);

    $component->call('toggleFavorite', $exercise->id);

    expect($this->user->favoriteExercises()->count())->toBe(0);
});

it('narrows the library to the pinned exercises', function () {
    $pinned = libraryExercise(['name' => 'Bench Press']);
    libraryExercise(['name' => 'Deadlift']);

    $this->user->favoriteExercises()->attach($pinned);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterSource', 'favorites')
        ->assertSee('Bench Press')
        ->assertDontSee('Deadlift');
});

it('keeps one user’s favorites out of another’s', function () {
    $exercise = libraryExercise();

    User::factory()->create()->favoriteExercises()->attach($exercise);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterSource', 'favorites')
        ->assertDontSee($exercise->name);
});

it('shows an exercise with its muscles and instructions', function () {
    $exercise = libraryExercise();

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->assertSee($exercise->name)
        ->assertSee(__('muscle.chest'))
        ->assertSee(__('muscle.triceps'))
        ->assertSee('Press the bar up.');
});

it('shows the instructions in the reader’s language', function () {
    $exercise = libraryExercise([
        'instructions' => ['en' => ['Press the bar up.'], 'fr' => ['Pousse la barre.']],
    ]);

    app()->setLocale('fr');

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->assertSee('Pousse la barre.')
        ->assertDontSee('Press the bar up.');
});

it('shows an exercise carrying no instructions at all', function () {
    $exercise = libraryExercise(['instructions' => []]);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->assertSee($exercise->name);

    expect($exercise->instructionSteps())->toBe([]);
});

it('falls back to the application language for a translation nobody wrote', function () {
    $exercise = libraryExercise([
        'instructions' => ['en' => ['Press the bar up.'], 'fr' => ['Pousse la barre.']],
    ]);

    expect($exercise->instructionSteps('fr'))->toBe(['Pousse la barre.'])
        ->and($exercise->instructionSteps('de'))->toBe(['Press the bar up.']);
});

it('pins an exercise from its own page', function () {
    $exercise = libraryExercise();

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->assertSee(__('Add to favorites'))
        ->call('toggleFavorite')
        ->assertSee(__('Favorite'));

    expect($this->user->favoriteExercises()->count())->toBe(1);
});

it('offers the pinned exercises in the picker', function () {
    $pinned = libraryExercise(['name' => 'Bench Press']);
    libraryExercise(['name' => 'Deadlift']);

    $this->user->favoriteExercises()->attach($pinned);

    Livewire::actingAs($this->user)
        ->test('exercise-picker')
        ->call('filterSource', 'favorites')
        ->assertSee('Bench Press')
        ->assertDontSee('Deadlift');
});

it('requires a signed-in user to browse the library', function () {
    $this->get(route('exercises.index'))->assertRedirect(route('login'));
});

it('reveals the library a page at a time', function () {
    foreach (range(1, 30) as $index) {
        libraryExercise(['name' => 'Exercise '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]);
    }

    $component = Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->assertSee('Exercise 01')
        ->assertDontSee('Exercise 30')
        ->assertSee(__('Load more'));

    $component->call('loadMore')
        ->assertSee('Exercise 30')
        ->assertDontSee(__('Load more'));
});

it('starts back at the first page when the search changes', function () {
    foreach (range(1, 30) as $index) {
        libraryExercise(['name' => 'Exercise '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]);
    }

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('loadMore')
        ->assertSet('shown', 48)
        ->set('term', 'Exercise 3')
        ->assertSet('shown', 24);
});

it('finds an exercise through the search engine', function () {
    libraryExercise(['name' => 'Barbell Bench Press']);
    libraryExercise(['name' => 'Leg Curl']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->set('term', 'bench')
        ->assertSee('Barbell Bench Press')
        ->assertDontSee('Leg Curl');
});

it('falls back to a name match when the engine cannot be reached', function () {
    libraryExercise(['name' => 'Barbell Bench Press']);
    libraryExercise(['name' => 'Leg Curl']);

    // Point Scout at an engine that is not there: the search must still answer.
    config(['scout.driver' => 'meilisearch', 'scout.meilisearch.host' => 'http://127.0.0.1:1']);

    expect(fn () => Exercise::search('Bench')->keys())->toThrow(CommunicationException::class);

    $found = app(ExerciseService::class)
        ->applyTerm(Exercise::query(), 'Bench')
        ->pluck('name')
        ->all();

    expect($found)->toBe(['Barbell Bench Press']);
});

it('keeps the engine ranking rather than sorting the matches by name', function () {
    // The engine hands its hits back best-first and the list must not resort
    // them. Under test the engine ranks newest first, so the alphabetically
    // last exercise is created last and has to come out on top.
    libraryExercise(['name' => 'Alpha Press']);
    libraryExercise(['name' => 'Zebra Press']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->set('term', 'Press')
        ->assertSeeInOrder(['Zebra Press', 'Alpha Press']);
});

it('adds an exercise the library does not carry', function () {
    Livewire::actingAs($this->user)
        ->test('pages::exercises.create')
        ->set('name', 'Farmer Walk')
        ->call('toggleMuscle', 'forearms')
        ->call('chooseEquipment', 'dumbbell')
        ->call('save')
        ->assertHasNoErrors();

    $exercise = $this->user->exercises()->firstOrFail();

    expect($exercise->name)->toBe('Farmer Walk')
        ->and($exercise->isCustom())->toBeTrue()
        ->and($exercise->primary_muscles)->toBe(['forearms'])
        ->and($exercise->equipment)->toBe('dumbbell')
        ->and($exercise->slug)->toBe('farmer-walk');
});

it('requires a name to add an exercise', function () {
    Livewire::actingAs($this->user)
        ->test('pages::exercises.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('gives an exercise a slug of its own when the name is taken', function () {
    libraryExercise(['name' => 'Farmer Walk', 'slug' => 'farmer-walk']);

    app(ExerciseService::class)->createCustom($this->user, ['name' => 'Farmer Walk']);

    expect($this->user->exercises()->firstOrFail()->slug)->toBe('farmer-walk-2');
});

it('keeps one user’s own exercises out of another’s library', function () {
    $mine = Exercise::factory()->ownedBy($this->user)->create(['name' => 'My Own Move']);
    $someoneElses = Exercise::factory()->ownedBy(User::factory()->create())->create(['name' => 'Their Own Move']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->assertSee($mine->name)
        ->assertDontSee($someoneElses->name);
});

it('refuses to show an exercise belonging to someone else', function () {
    $someoneElses = Exercise::factory()->ownedBy(User::factory()->create())->create();

    $this->actingAs($this->user)
        ->get(route('exercises.show', $someoneElses))
        ->assertForbidden();
});

it('deletes an exercise the user added', function () {
    $exercise = Exercise::factory()->ownedBy($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->call('confirmDelete')
        ->assertSet('confirmingDelete', true)
        ->call('delete');

    expect(Exercise::find($exercise->id))->toBeNull();
});

it('refuses to delete a library exercise', function () {
    $exercise = libraryExercise();

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->call('delete')
        ->assertForbidden();

    expect(Exercise::find($exercise->id))->not->toBeNull();
});

it('charts the user’s own progression on an exercise', function () {
    $exercise = libraryExercise();
    $training = Training::factory()->for($this->user)->create();
    $activity = Activity::factory()->create(['training_id' => $training->id, 'exercise_id' => $exercise->id]);

    Sequence::factory()->for($activity)->create(['repetition' => 10, 'weight' => 60]);

    // Someone else lifting the same library exercise must not show up here.
    $theirs = Activity::factory()->create(['exercise_id' => $exercise->id]);
    Sequence::factory()->for($theirs)->create(['repetition' => 8, 'weight' => 999]);

    $values = app(ExerciseService::class)->getValues($exercise, $this->user);

    expect($values['weight'])->toBe([60.0])
        ->and($values['repetition'])->toBe([10]);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise])
        ->assertSee(__('Your progress'));
});

it('offers the exercises the user already logged as shortcuts', function () {
    $logged = libraryExercise(['name' => 'Logged Move']);
    $never = libraryExercise(['name' => 'Never Used']);

    $training = Training::factory()->for($this->user)->create();
    Activity::factory()->create(['training_id' => $training->id, 'exercise_id' => $logged->id]);

    // Both show in the picker below; only the logged one is a shortcut.
    Livewire::actingAs($this->user)
        ->test('pages::trainings.show', ['training' => $training])
        ->call('pick')
        ->assertSee("addActivity({$logged->id}", escape: false)
        ->assertDontSee("addActivity({$never->id}", escape: false);
});

it('narrows the library to the exercises the user added', function () {
    $mine = Exercise::factory()->ownedBy($this->user)->create(['name' => 'My Own Move']);
    libraryExercise(['name' => 'Imported Move']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterSource', 'custom')
        ->assertSee($mine->name)
        ->assertDontSee('Imported Move');
});

it('narrows the library to the imported exercises', function () {
    Exercise::factory()->ownedBy($this->user)->create(['name' => 'My Own Move']);
    libraryExercise(['name' => 'Imported Move']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterSource', 'imported')
        ->assertSee('Imported Move')
        ->assertDontSee('My Own Move');
});

it('still has the filters on when the list is opened again', function () {
    libraryExercise(['name' => 'Imported Move', 'primary_muscles' => ['chest']]);
    Exercise::factory()->ownedBy($this->user)->create(['name' => 'My Own Move']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->set('term', 'Move')
        ->call('filterMuscle', 'chest')
        ->call('filterSource', 'imported');

    // A fresh mount, as the chevron on an exercise page gives you.
    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->assertSet('term', 'Move')
        ->assertSet('muscle', 'chest')
        ->assertSet('source', 'imported')
        ->assertSee('Imported Move')
        ->assertDontSee('My Own Move');
});

it('forgets a filter that was toggled back off', function () {
    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterMuscle', 'chest')
        ->call('filterMuscle', 'chest');

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->assertSet('muscle', null);
});

it('drops the source filter when the same one is tapped again', function () {
    Exercise::factory()->ownedBy($this->user)->create(['name' => 'My Own Move']);
    libraryExercise(['name' => 'Imported Move']);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.index')
        ->call('filterSource', 'custom')
        ->assertSet('source', 'custom')
        ->call('filterSource', 'custom')
        ->assertSet('source', null)
        ->assertSee('My Own Move')
        ->assertSee('Imported Move');
});

it('keeps the illustration disks readable without opening the generic one', function () {
    expect(config('filesystems.disks.public.visibility'))->toBe('public')
        ->and(config('filesystems.disks.media.visibility'))->toBe('public')
        // `s3` is where anything else would land, so it stays private.
        ->and(config('filesystems.disks.s3'))->not->toHaveKey('visibility');
});

it('shows the illustrations at their own size rather than stretched to the column', function () {
    Storage::fake(config('media-library.disk_name'));

    $exercise = libraryExercise();

    $exercise->addMediaFromString(jpegFixture())
        ->usingFileName('bench-press-0.jpg')
        ->toMediaCollection(Exercise::ILLUSTRATIONS);

    Livewire::actingAs($this->user)
        ->test('pages::exercises.show', ['exercise' => $exercise->fresh()])
        // `w-full` upscales a 850px source into the column and it turns to mush.
        ->assertSeeHtml('max-h-72 w-auto max-w-full')
        ->assertDontSeeHtml('w-full rounded-2xl bg-raised object-cover');
});

it('stores the illustrations on the disk the configuration names', function () {
    config()->set('media-library.disk_name', 's3');
    Storage::fake('s3');

    $exercise = libraryExercise();

    $exercise->addMediaFromString(jpegFixture())
        ->usingFileName('bench-press-0.jpg')
        ->toMediaCollection(Exercise::ILLUSTRATIONS);

    $illustration = $exercise->getFirstMedia(Exercise::ILLUSTRATIONS);

    expect($illustration->disk)->toBe('s3');

    Storage::disk('s3')->assertExists("{$illustration->id}/bench-press-0.jpg");
});
