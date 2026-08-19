<?php

use App\Console\Commands\ImportExercises;
use App\Models\Exercise;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The upstream payload, in the shape free-exercise-db publishes.
 *
 * @return list<array<string, mixed>>
 */
function upstreamExercises(): array
{
    return [
        [
            'name' => 'Barbell Bench Press',
            'category' => 'strength',
            'level' => 'beginner',
            'force' => 'push',
            'mechanic' => 'compound',
            'equipment' => 'barbell',
            'primaryMuscles' => ['chest'],
            'secondaryMuscles' => ['triceps'],
            'instructions' => ['Lie on the bench.'],
            'images' => ['Barbell_Bench_Press/0.jpg', 'Barbell_Bench_Press/1.jpg'],
        ],
        [
            'name' => 'Bodyweight Squat',
            'category' => 'strength',
            'level' => 'beginner',
            'primaryMuscles' => ['quadriceps'],
            'secondaryMuscles' => [],
            'instructions' => ['Stand tall.'],
            'images' => ['Bodyweight_Squat/0.jpg'],
        ],
    ];
}

/**
 * Stub the upstream host. Http::fake() merges stubs rather than replacing them,
 * so each test declares the whole set it wants rather than layering.
 */
function fakeUpstream(?int $dataStatus = null, ?int $imageStatus = null): void
{
    Http::fake([
        '*dist/exercises.json' => $dataStatus === null
            ? Http::response(upstreamExercises(), 200)
            : Http::response('nope', $dataStatus),
        '*exercises/*' => $imageStatus === null
            ? Http::response(jpegFixture(), 200)
            : Http::response('nope', $imageStatus),
    ]);
}

beforeEach(function () {
    Storage::fake(config('media-library.disk_name'));
});

it('imports nothing until the command is run', function () {
    expect(Exercise::count())->toBe(0);
});

it('imports the library without illustrations by default', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--without-images' => true])->assertSuccessful();

    expect(Exercise::count())->toBe(2);

    $exercise = Exercise::where('slug', 'barbell-bench-press')->firstOrFail();

    expect($exercise->name)->toBe('Barbell Bench Press')
        ->and($exercise->primary_muscles)->toBe(['chest'])
        ->and($exercise->equipment)->toBe('barbell')
        ->and($exercise->source)->toBe(ImportExercises::SOURCE)
        // This library is English throughout, so that is the language it lands in.
        ->and($exercise->getTranslations('instructions'))->toBe(['en' => ['Lie on the bench.']])
        ->and($exercise->image_paths)->toHaveCount(2)
        ->and($exercise->hasIllustrations())->toBeFalse();
});

it('downloads the illustrations when asked', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--with-images' => true])->assertSuccessful();

    expect(Exercise::where('slug', 'barbell-bench-press')->firstOrFail()->getMedia(Exercise::ILLUSTRATIONS))
        ->toHaveCount(2);
});

it('updates entries it already imported rather than duplicating them', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--without-images' => true])->assertSuccessful();
    $this->artisan('dofit:import-exercises', ['--without-images' => true])->assertSuccessful();

    expect(Exercise::count())->toBe(2);
});

it('leaves already-fetched illustrations alone on a second run', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--with-images' => true])->assertSuccessful();

    $before = Http::recorded()->count();

    $this->artisan('dofit:import-exercises', ['--with-images' => true])->assertSuccessful();

    // Only the dataset is downloaded again, not the three images.
    expect(Http::recorded()->count())->toBe($before + 1);
});

it('refetches illustrations when forced', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--with-images' => true])->assertSuccessful();
    $this->artisan('dofit:import-exercises', ['--with-images' => true, '--force' => true])->assertSuccessful();

    expect(Exercise::where('slug', 'barbell-bench-press')->firstOrFail()->getMedia(Exercise::ILLUSTRATIONS))
        ->toHaveCount(2);
});

it('honours the limit', function () {
    fakeUpstream();

    $this->artisan('dofit:import-exercises', ['--without-images' => true, '--limit' => 1])->assertSuccessful();

    expect(Exercise::count())->toBe(1);
});

it('fails when the dataset cannot be downloaded', function () {
    fakeUpstream(dataStatus: 503);

    $this->artisan('dofit:import-exercises', ['--without-images' => true])->assertFailed();

    expect(Exercise::count())->toBe(0);
});

it('reports illustrations it could not fetch', function () {
    fakeUpstream(imageStatus: 404);

    $this->artisan('dofit:import-exercises', ['--with-images' => true])->assertFailed();

    expect(Exercise::count())->toBe(2)
        ->and(Exercise::has('media')->count())->toBe(0);
});
