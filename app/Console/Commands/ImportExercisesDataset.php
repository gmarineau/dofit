<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportExercisesDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dofit:import-exercises-dataset
                            {--with-media : Also download the illustrations and animations, which are not ours to redistribute}
                            {--limit= : Only import this many exercises}
                            {--force : Re-download media that is already stored}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the multilingual exercise library from exercises-dataset.';

    /**
     * What `exercises.source` is stamped with for the rows this command owns.
     */
    public const string SOURCE = 'exercises-dataset';

    /**
     * The upstream dataset. The exercise data is MIT; the media is not, which
     * is why fetching it is opt-in.
     */
    protected const string DATA_URL = 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/data/exercises.json';

    /**
     * Where the matching media lives. `image_paths` holds paths relative to
     * this, which is what `source` is read for when fetching.
     */
    protected const string MEDIA_URL = 'https://raw.githubusercontent.com/hasaneyldrm/exercises-dataset/main/';

    /**
     * The muscles the library already speaks, taken from free-exercise-db so
     * both imports describe an exercise the same way.
     *
     * @var list<string>
     */
    protected const array MUSCLES = [
        'abdominals', 'abductors', 'adductors', 'biceps', 'calves', 'chest',
        'forearms', 'glutes', 'hamstrings', 'lats', 'lower back', 'middle back',
        'neck', 'quadriceps', 'shoulders', 'traps', 'triceps',
    ];

    /**
     * How this dataset's anatomy names read in the library's vocabulary. Only
     * the renames are listed: a name already in `MUSCLES` is kept as it is.
     *
     * @var array<string, string>
     */
    protected const array MUSCLE_ALIASES = [
        'abs' => 'abdominals',
        'ankle stabilizers' => 'calves',
        'ankles' => 'calves',
        'back' => 'middle back',
        'brachialis' => 'biceps',
        'core' => 'abdominals',
        'deltoids' => 'shoulders',
        'delts' => 'shoulders',
        'feet' => 'calves',
        'grip muscles' => 'forearms',
        'groin' => 'adductors',
        'hands' => 'forearms',
        'hip flexors' => 'quadriceps',
        'inner thighs' => 'adductors',
        'latissimus dorsi' => 'lats',
        'levator scapulae' => 'neck',
        'lower abs' => 'abdominals',
        'obliques' => 'abdominals',
        'pectorals' => 'chest',
        'quads' => 'quadriceps',
        'rear deltoids' => 'shoulders',
        'rhomboids' => 'middle back',
        'rotator cuff' => 'shoulders',
        'serratus anterior' => 'chest',
        'shins' => 'calves',
        'soleus' => 'calves',
        'spine' => 'lower back',
        'sternocleidomastoid' => 'neck',
        'trapezius' => 'traps',
        'upper back' => 'middle back',
        'upper chest' => 'chest',
        'wrist extensors' => 'forearms',
        'wrist flexors' => 'forearms',
        'wrists' => 'forearms',
        // Carries no muscle at all, so it is dropped rather than mapped.
        'cardiovascular system' => '',
    ];

    /**
     * How this dataset's 28 kinds of equipment read in the library's
     * vocabulary, which free-exercise-db set.
     *
     * @var array<string, string>
     */
    protected const array EQUIPMENT_ALIASES = [
        'assisted' => 'machine',
        'band' => 'bands',
        'body weight' => 'body only',
        'bosu ball' => 'exercise ball',
        'elliptical machine' => 'machine',
        'ez barbell' => 'e-z curl bar',
        'hammer' => 'other',
        'kettlebell' => 'kettlebells',
        'leverage machine' => 'machine',
        'olympic barbell' => 'barbell',
        'resistance band' => 'bands',
        'roller' => 'foam roll',
        'rope' => 'other',
        'skierg machine' => 'machine',
        'sled machine' => 'machine',
        'smith machine' => 'machine',
        'stability ball' => 'exercise ball',
        'stationary bike' => 'machine',
        'stepmill machine' => 'machine',
        'tire' => 'other',
        'trap bar' => 'barbell',
        'upper body ergometer' => 'machine',
        'weighted' => 'other',
        'wheel roller' => 'foam roll',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $locales = $this->locales();

        if ($locales === []) {
            $this->components->error('None of the languages in config(\'dofit.locales\') are in this dataset.');

            return self::FAILURE;
        }

        $this->components->info('Importing exercises-dataset ('.implode(', ', $locales).').');

        try {
            $exercises = $this->download($locales);
        } catch (Throwable $e) {
            $this->components->error("Could not download the dataset: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($limit = $this->option('limit')) {
            $exercises = $exercises->take((int) $limit);
        }

        [$created, $enriched] = $this->store($exercises);

        $this->components->info("{$created} exercises added, {$enriched} existing ones gained a translation.");

        // The import upserts in bulk, so Scout's model events never fire.
        $this->components->warn('Run `dofit:sync-exercise-search` to refresh the search index.');

        if (! $this->option('with-media')) {
            $this->components->info('Media skipped. It belongs to Gym visual, so only fetch it for a local library you do not serve to others.');

            return self::SUCCESS;
        }

        return $this->fetchMedia();
    }

    /**
     * The languages this application offers that the dataset actually carries.
     *
     * @return list<string>
     */
    protected function locales(): array
    {
        return array_values(array_intersect(
            array_keys((array) config('dofit.locales')),
            ['en', 'es', 'fr', 'hi', 'it', 'ko', 'pl', 'ru', 'tr', 'zh'],
        ));
    }

    /**
     * Fetch the upstream dataset and normalise it. Only the languages this
     * application offers are kept: the payload carries ten of them.
     *
     * @param  list<string>  $locales
     * @return Collection<int, array<string, mixed>>
     */
    protected function download(array $locales): Collection
    {
        $response = Http::timeout(120)->retry(2, 500)->get(self::DATA_URL);

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()}");
        }

        return collect((array) $response->json())
            ->map(fn (mixed $exercise): array => $this->toRow((array) $exercise, $locales))
            ->unique('slug')
            ->sortBy('name')
            ->values();
    }

    /**
     * Turn one upstream entry into a row ready to be written.
     *
     * @param  array<string, mixed>  $exercise
     * @param  list<string>  $locales
     * @return array<string, mixed>
     */
    protected function toRow(array $exercise, array $locales): array
    {
        $primary = $this->muscles([$exercise['target'] ?? null, $exercise['muscle_group'] ?? null]);

        return [
            'slug' => Str::slug((string) $exercise['name']),
            'source' => self::SOURCE,
            'name' => $exercise['name'],
            // This dataset groups by body part and carries no kind of effort,
            // which is what `category` holds for free-exercise-db rows.
            'category' => null,
            'body_part' => $exercise['body_part'] ?? $exercise['category'] ?? null,
            'level' => null,
            'force' => null,
            'mechanic' => null,
            'equipment' => $this->equipment($exercise['equipment'] ?? null),
            'primary_muscles' => json_encode($primary),
            'secondary_muscles' => json_encode(array_values(array_diff(
                $this->muscles((array) ($exercise['secondary_muscles'] ?? [])),
                $primary,
            ))),
            'instructions' => json_encode($this->instructions($exercise, $locales)),
            'image_paths' => json_encode(array_values(array_filter([
                $exercise['image'] ?? null,
                $exercise['gif_url'] ?? null,
            ]))),
        ];
    }

    /**
     * The instruction steps of one entry, keyed by locale and stripped of the
     * languages this application does not offer.
     *
     * @param  array<string, mixed>  $exercise
     * @param  list<string>  $locales
     * @return array<string, list<string>>
     */
    protected function instructions(array $exercise, array $locales): array
    {
        $steps = (array) ($exercise['instruction_steps'] ?? []);

        $translations = [];

        foreach ($locales as $locale) {
            $translated = array_values(array_filter(
                (array) ($steps[$locale] ?? []),
                fn (mixed $step): bool => filled($step),
            ));

            if ($translated !== []) {
                $translations[$locale] = $translated;
            }
        }

        return $translations;
    }

    /**
     * Read a list of anatomy names in the library's vocabulary, dropping the
     * ones that have no equivalent.
     *
     * @param  array<array-key, mixed>  $names
     * @return list<string>
     */
    protected function muscles(array $names): array
    {
        return collect($names)
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => Str::lower(trim((string) $name)))
            ->map(fn (string $name): string => self::MUSCLE_ALIASES[$name] ?? $name)
            ->filter(fn (string $name): bool => in_array($name, self::MUSCLES, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Read a kind of equipment in the library's vocabulary.
     */
    protected function equipment(mixed $equipment): ?string
    {
        if (blank($equipment)) {
            return null;
        }

        $name = Str::lower(trim((string) $equipment));

        return self::EQUIPMENT_ALIASES[$name] ?? $name;
    }

    /**
     * Write the exercises. A slug this dataset shares with another import is
     * only ever given its translations: everything else on that row was set by
     * whichever import owns it, and is left alone.
     *
     * @param  Collection<int, array<string, mixed>>  $exercises
     * @return array{0: int, 1: int}
     */
    protected function store(Collection $exercises): array
    {
        $created = 0;
        $enriched = 0;

        $exercises->chunk(200)->each(function (Collection $chunk) use (&$created, &$enriched): void {
            $rows = $chunk->keyBy('slug');

            $existing = DB::table('exercises')
                ->whereIn('slug', $rows->keys())
                ->get(['slug', 'source', 'instructions'])
                ->keyBy('slug');

            $ours = $rows
                ->reject(fn (array $row, string $slug): bool => isset($existing[$slug])
                    && $existing[$slug]->source !== self::SOURCE)
                ->values();

            if ($ours->isNotEmpty()) {
                Exercise::upsert(
                    $ours->all(),
                    uniqueBy: ['slug'],
                    // `instructions` stays out so a language another import
                    // added is never dropped; it is merged in below instead.
                    update: [
                        'name', 'source', 'category', 'body_part', 'equipment',
                        'primary_muscles', 'secondary_muscles', 'image_paths',
                    ],
                );
            }

            $created += $rows->keys()->diff($existing->keys())->count();

            foreach ($existing as $slug => $row) {
                $enriched += $this->mergeInstructions(
                    $slug,
                    $row->instructions,
                    $rows[$slug]['instructions'],
                    ours: $row->source === self::SOURCE,
                );
            }
        });

        return [$created, $enriched];
    }

    /**
     * Add this dataset's languages to what an exercise already carries, and
     * report whether that changed anything.
     *
     * On a row this import owns, the freshly downloaded wording wins. On one
     * another import owns, that import's wording stands and this dataset only
     * fills in the languages missing from it — which is the whole point of
     * running it over a library that is already there.
     */
    protected function mergeInstructions(string $slug, ?string $stored, string $incoming, bool $ours): int
    {
        $current = json_decode((string) $stored, associative: true);
        $current = is_array($current) && ! array_is_list($current) ? $current : [];

        $adding = (array) json_decode($incoming, associative: true);

        $merged = $ours ? [...$current, ...$adding] : [...$adding, ...$current];

        if ($merged === $current) {
            return 0;
        }

        DB::table('exercises')->where('slug', $slug)->update(['instructions' => json_encode($merged)]);

        return 1;
    }

    /**
     * Download the media into the library. Runs are resumable: exercises that
     * already have theirs are skipped.
     */
    protected function fetchMedia(): int
    {
        $this->components->warn('The media belongs to Gym visual (https://gymvisual.com/). Keep the attribution and do not redistribute it.');

        $ids = Exercise::query()
            ->where('source', self::SOURCE)
            ->whereJsonLength('image_paths', '>', 0)
            ->when(! $this->option('force'), fn ($query) => $query->whereDoesntHave('media'))
            ->when($this->option('limit'), fn ($query, $limit) => $query->limit((int) $limit))
            ->orderBy('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->components->info('Every exercise already has its media.');

            return self::SUCCESS;
        }

        $this->components->info("Fetching media for {$ids->count()} exercises.");

        $bar = $this->output->createProgressBar($ids->count());
        $bar->start();

        $failed = 0;

        // A closure, not an arrow function: the latter would capture $failed by
        // value and the count would never come back out.
        $ids->chunk(100)->each(function (Collection $chunk) use ($bar, &$failed): void {
            Exercise::whereIn('id', $chunk)->get()->each(function (Exercise $exercise) use ($bar, &$failed): void {
                try {
                    $this->fetchMediaFor($exercise);
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
     * Download and attach the thumbnail and animation of one exercise.
     */
    protected function fetchMediaFor(Exercise $exercise): void
    {
        if ($this->option('force')) {
            $exercise->clearMediaCollection(Exercise::ILLUSTRATIONS);
        }

        foreach ($exercise->image_paths ?? [] as $index => $path) {
            $response = Http::timeout(20)->retry(2, 200)->get(self::MEDIA_URL.$path);

            if ($response->failed()) {
                throw new RuntimeException("HTTP {$response->status()} on {$path}");
            }

            $exercise
                ->addMediaFromString($response->body())
                ->usingFileName("{$exercise->slug}-{$index}.".pathinfo((string) $path, PATHINFO_EXTENSION))
                ->toMediaCollection(Exercise::ILLUSTRATIONS);
        }
    }
}
