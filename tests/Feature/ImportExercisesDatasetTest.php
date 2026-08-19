<?php

use App\Console\Commands\ImportExercises;
use App\Console\Commands\ImportExercisesDataset;
use App\Models\Exercise;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The upstream payload, in the shape exercises-dataset publishes. Russian is
 * there on purpose: the application does not offer it, so it must be dropped.
 *
 * @return list<array<string, mixed>>
 */
function datasetExercises(): array
{
    return [
        [
            'id' => '0001',
            'name' => 'Barbell Bench Press',
            'category' => 'chest',
            'body_part' => 'chest',
            'equipment' => 'olympic barbell',
            'target' => 'pectorals',
            'muscle_group' => 'triceps',
            'secondary_muscles' => ['deltoids', 'pectorals', 'cardiovascular system'],
            'instruction_steps' => [
                'en' => ['Lie on the bench.', 'Press the bar up.'],
                'fr' => ['Allonge-toi sur le banc.', 'Pousse la barre.'],
                'ru' => ['Лягте на скамью.'],
            ],
            'image' => 'images/0001-abc.jpg',
            'gif_url' => 'videos/0001-abc.gif',
            'attribution' => '© Gym visual — https://gymvisual.com/',
        ],
        [
            'id' => '0002',
            'name' => 'Assisted Pull-up',
            'category' => 'back',
            'body_part' => 'back',
            'equipment' => 'assisted',
            'target' => 'lats',
            'muscle_group' => 'latissimus dorsi',
            'secondary_muscles' => ['biceps'],
            'instruction_steps' => [
                'en' => ['Grab the bar.'],
                'fr' => ['Attrape la barre.'],
            ],
            'image' => 'images/0002-def.jpg',
            'gif_url' => 'videos/0002-def.gif',
            'attribution' => '© Gym visual — https://gymvisual.com/',
        ],
    ];
}

/**
 * Stub the upstream host. Http::fake() merges stubs rather than replacing them,
 * so each test declares the whole set it wants rather than layering.
 */
function fakeDataset(?int $dataStatus = null, ?int $mediaStatus = null): void
{
    Http::fake([
        '*exercises-dataset/main/data/exercises.json' => $dataStatus === null
            ? Http::response(datasetExercises(), 200)
            : Http::response('nope', $dataStatus),
        '*exercises-dataset/main/images/*' => $mediaStatus === null
            ? Http::response(jpegFixture(), 200)
            : Http::response('nope', $mediaStatus),
        '*exercises-dataset/main/videos/*' => $mediaStatus === null
            ? Http::response(gifFixture(), 200)
            : Http::response('nope', $mediaStatus),
    ]);
}

beforeEach(function () {
    Storage::fake(config('media-library.disk_name'));
});

it('imports the dataset without media by default', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    expect(Exercise::count())->toBe(2);

    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    expect($exercise->source)->toBe(ImportExercisesDataset::SOURCE)
        ->and($exercise->image_paths)->toHaveCount(2)
        ->and($exercise->hasIllustrations())->toBeFalse();
});

it('keeps only the languages the application offers', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    expect($exercise->getTranslations('instructions'))->toHaveKeys(['en', 'fr'])
        ->and($exercise->getTranslations('instructions'))->not->toHaveKey('ru')
        ->and($exercise->instructionSteps('fr'))->toBe(['Allonge-toi sur le banc.', 'Pousse la barre.'])
        ->and($exercise->instructionSteps('en'))->toBe(['Lie on the bench.', 'Press the bar up.']);
});

it('reads equipment and anatomy in the library vocabulary', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    $bench = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();
    $pullUp = Exercise::where('slug', 'assisted-pull-up')->firstOrFail();

    expect($bench->equipment)->toBe('barbell')
        ->and($pullUp->equipment)->toBe('machine')
        // target and muscle_group both feed the primary muscles.
        ->and($bench->primary_muscles)->toBe(['chest', 'triceps'])
        // deltoids becomes shoulders, a muscle already counted as primary is
        // not repeated, and what maps to nothing is dropped.
        ->and($bench->secondary_muscles)->toBe(['shoulders'])
        ->and($pullUp->primary_muscles)->toBe(['lats']);
});

it('records the body part and leaves the kind of effort empty', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    expect($exercise->body_part)->toBe('chest')
        ->and($exercise->category)->toBeNull();
});

it('only adds translations to an exercise another import owns', function () {
    // The row free-exercise-db would have written, English only.
    $existing = Exercise::factory()->create([
        'slug' => 'barbell-bench-press',
        'name' => 'Barbell Bench Press',
        'source' => ImportExercises::SOURCE,
        'category' => 'strength',
        'level' => 'beginner',
        'equipment' => 'barbell',
        'primary_muscles' => ['chest'],
        'instructions' => ['en' => ['Lie on the bench.']],
    ]);

    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    $existing->refresh();

    expect(Exercise::count())->toBe(2)
        // Everything the other import set is untouched.
        ->and($existing->source)->toBe(ImportExercises::SOURCE)
        ->and($existing->category)->toBe('strength')
        ->and($existing->level)->toBe('beginner')
        ->and($existing->primary_muscles)->toBe(['chest'])
        // And it gained the French without losing the English.
        ->and($existing->instructionSteps('en'))->toBe(['Lie on the bench.'])
        ->and($existing->instructionSteps('fr'))->toBe(['Allonge-toi sur le banc.', 'Pousse la barre.']);
});

it('downloads the media when asked', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset', ['--with-media' => true])->assertSuccessful();

    expect(Exercise::where('slug', 'barbell-bench-press')->firstOrFail()->getMedia(Exercise::ILLUSTRATIONS))
        ->toHaveCount(2);
});

it('updates entries it already imported rather than duplicating them', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();
    $this->artisan('dofit:import-exercises-dataset')->assertSuccessful();

    expect(Exercise::count())->toBe(2);
});

it('honours the limit', function () {
    fakeDataset();

    $this->artisan('dofit:import-exercises-dataset', ['--limit' => 1])->assertSuccessful();

    expect(Exercise::count())->toBe(1);
});

it('fails when the dataset cannot be downloaded', function () {
    fakeDataset(dataStatus: 503);

    $this->artisan('dofit:import-exercises-dataset')->assertFailed();

    expect(Exercise::count())->toBe(0);
});

it('reports media it could not fetch', function () {
    fakeDataset(mediaStatus: 404);

    $this->artisan('dofit:import-exercises-dataset', ['--with-media' => true])->assertFailed();

    expect(Exercise::count())->toBe(2)
        ->and(Exercise::has('media')->count())->toBe(0);
});
