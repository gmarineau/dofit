<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\confirm;

class ImportExercises extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dofit:import-exercises
                            {--with-images : Also download the illustrations (~100 MB)}
                            {--without-images : Skip the illustrations without being asked}
                            {--limit= : Only import this many exercises}
                            {--force : Re-download illustrations that are already stored}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the optional exercise library from free-exercise-db.';

    /**
     * The upstream dataset, released into the public domain under the Unlicense.
     */
    protected const string DATA_URL = 'https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/dist/exercises.json';

    /**
     * Where the matching illustrations live.
     */
    protected const string IMAGE_URL = 'https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises/';

    /**
     * Execute the console command.
     *
     * The library is optional: DoFit works with the exercise names people type
     * themselves, and this command is what fills the picker with a catalogue.
     */
    public function handle(): int
    {
        $this->components->info('Importing the exercise library from free-exercise-db (public domain).');

        try {
            $exercises = $this->download();
        } catch (Throwable $e) {
            $this->components->error("Could not download the library: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($limit = $this->option('limit')) {
            $exercises = $exercises->take((int) $limit);
        }

        $this->store($exercises);

        $this->components->info("{$exercises->count()} exercises imported.");

        if (! $this->shouldFetchImages()) {
            $this->components->info('Illustrations skipped. Run this command again with --with-images to add them.');

            return self::SUCCESS;
        }

        return $this->fetchImages();
    }

    /**
     * Fetch the upstream dataset and normalise it.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function download(): Collection
    {
        $response = Http::timeout(60)->retry(2, 500)->get(self::DATA_URL);

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()}");
        }

        return collect((array) $response->json())
            ->map(fn (mixed $exercise): array => $this->toRow((array) $exercise))
            ->unique('slug')
            ->sortBy('name')
            ->values();
    }

    /**
     * Turn one upstream entry into a row ready to be upserted.
     *
     * @param  array<string, mixed>  $exercise
     * @return array<string, mixed>
     */
    protected function toRow(array $exercise): array
    {
        return [
            'slug' => Str::slug((string) $exercise['name']),
            'name' => $exercise['name'],
            'category' => $exercise['category'] ?? null,
            'level' => $exercise['level'] ?? null,
            'force' => $exercise['force'] ?? null,
            'mechanic' => $exercise['mechanic'] ?? null,
            'equipment' => $exercise['equipment'] ?? null,
            'primary_muscles' => json_encode($exercise['primaryMuscles'] ?? []),
            'secondary_muscles' => json_encode($exercise['secondaryMuscles'] ?? []),
            'instructions' => json_encode($exercise['instructions'] ?? []),
            'image_paths' => json_encode($exercise['images'] ?? []),
        ];
    }

    /**
     * Write the exercises, updating the ones already present.
     *
     * @param  Collection<int, array<string, mixed>>  $exercises
     */
    protected function store(Collection $exercises): void
    {
        $exercises->chunk(200)->each(fn (Collection $chunk) => Exercise::upsert(
            $chunk->all(),
            uniqueBy: ['slug'],
            update: [
                'name', 'category', 'level', 'force', 'mechanic', 'equipment',
                'primary_muscles', 'secondary_muscles', 'instructions', 'image_paths',
            ],
        ));
    }

    /**
     * Decide whether to pull the illustrations down as well, asking when the
     * command is being run by hand and neither flag was given.
     */
    protected function shouldFetchImages(): bool
    {
        if ($this->option('with-images')) {
            return true;
        }

        if ($this->option('without-images') || ! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: 'Download the illustrations too?',
            default: false,
            hint: 'Roughly 100 MB for two images per exercise.',
        );
    }

    /**
     * Download the illustrations into the media library. Runs are resumable:
     * exercises that already have their images are skipped.
     */
    protected function fetchImages(): int
    {
        // Resolve the ids up front: chunked iteration would otherwise replace
        // the limit with its own paging.
        $ids = Exercise::query()
            ->whereJsonLength('image_paths', '>', 0)
            ->when(! $this->option('force'), fn ($query) => $query->whereDoesntHave('media'))
            ->when($this->option('limit'), fn ($query, $limit) => $query->limit((int) $limit))
            ->orderBy('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->components->info('Every exercise already has its illustrations.');

            return self::SUCCESS;
        }

        $this->components->info("Fetching illustrations for {$ids->count()} exercises.");

        $bar = $this->output->createProgressBar($ids->count());
        $bar->start();

        $failed = 0;

        // A closure, not an arrow function: the latter would capture $failed by
        // value and the count would never come back out.
        $ids->chunk(100)->each(function (Collection $chunk) use ($bar, &$failed): void {
            Exercise::whereIn('id', $chunk)->get()->each(function (Exercise $exercise) use ($bar, &$failed): void {
                try {
                    $this->fetchImagesFor($exercise);
                } catch (Throwable $e) {
                    $failed++;

                    $this->newLine();
                    $this->components->warn("{$exercise->name}: {$e->getMessage()}");
                }

                $bar->advance();
            });
        });

        $bar->finish();
        $this->newLine(2);

        if ($failed > 0) {
            $this->components->warn("{$failed} exercises could not be fetched. Run the command again to retry them.");

            return self::FAILURE;
        }

        $this->components->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Download and attach every illustration of one exercise.
     */
    protected function fetchImagesFor(Exercise $exercise): void
    {
        if ($this->option('force')) {
            $exercise->clearMediaCollection(Exercise::ILLUSTRATIONS);
        }

        foreach ($exercise->image_paths ?? [] as $index => $path) {
            $response = Http::timeout(20)->retry(2, 200)->get(self::IMAGE_URL.$path);

            if ($response->failed()) {
                throw new RuntimeException("HTTP {$response->status()} on {$path}");
            }

            $exercise
                ->addMediaFromString($response->body())
                ->usingFileName("{$exercise->slug}-{$index}.jpg")
                ->toMediaCollection(Exercise::ILLUSTRATIONS);
        }
    }
}
