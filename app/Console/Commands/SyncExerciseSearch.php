<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;
use Meilisearch\Client;
use Throwable;

class SyncExerciseSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dofit:sync-exercise-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the exercise search index and fill it with the library.';

    /**
     * What the engine matches on, in order of importance.
     *
     * @var list<string>
     */
    protected const array SEARCHABLE = ['name', 'primary_muscles', 'secondary_muscles', 'equipment', 'category'];

    /**
     * What the engine can filter on, should filtering ever move engine-side.
     *
     * @var list<string>
     */
    protected const array FILTERABLE = ['equipment', 'category', 'primary_muscles', 'secondary_muscles'];

    /**
     * The library is in English while the interface is in French. Rather than
     * translating 873 names, the engine is taught the gym vocabulary: each
     * French word points at the English one the names actually use.
     *
     * @var array<string, list<string>>
     */
    protected const array SYNONYMS = [
        'développé' => ['press'],
        'couché' => ['bench'],
        'incliné' => ['incline'],
        'décliné' => ['decline'],
        'militaire' => ['military'],
        'soulevé de terre' => ['deadlift'],
        'tirage' => ['pulldown', 'row'],
        'rowing' => ['row'],
        'traction' => ['pull up', 'chin up'],
        'pompes' => ['push up'],
        'dips' => ['dip'],
        'fente' => ['lunge'],
        'fentes' => ['lunge'],
        'flexion' => ['curl'],
        'extension' => ['extension'],
        'élévation' => ['raise'],
        'élévations' => ['raise'],
        'latérale' => ['lateral'],
        'latérales' => ['lateral'],
        'écarté' => ['fly'],
        'écartés' => ['fly'],
        'gainage' => ['plank'],
        'planche' => ['plank'],
        'crunch' => ['crunch'],
        'relevé de jambes' => ['leg raise'],
        'poulie' => ['cable'],
        'haltère' => ['dumbbell'],
        'haltères' => ['dumbbell'],
        'barre' => ['barbell'],
        'machine' => ['machine'],
        'corde' => ['rope'],
        'élastique' => ['band'],
        'pectoraux' => ['chest'],
        'pecs' => ['chest'],
        'épaules' => ['shoulder'],
        'deltoïdes' => ['shoulder', 'deltoid'],
        'dos' => ['back'],
        'dorsaux' => ['lats'],
        'trapèzes' => ['trap'],
        'abdominaux' => ['abdominal', 'abs'],
        'abdos' => ['abdominal', 'abs'],
        'lombaires' => ['lower back'],
        'cuisses' => ['quadriceps'],
        'quadriceps' => ['quadriceps'],
        'ischios' => ['hamstring'],
        'ischio-jambiers' => ['hamstring'],
        'fessiers' => ['glute'],
        'mollets' => ['calf', 'calves'],
        'avant-bras' => ['forearm'],
        'jambes' => ['leg'],
        'assis' => ['seated'],
        'debout' => ['standing'],
        'inversé' => ['reverse'],
        'genou' => ['knee'],
        'sauté' => ['jump'],
        'prise large' => ['wide grip'],
        'prise serrée' => ['close grip'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('scout.driver') === 'meilisearch' && ! $this->configureIndex()) {
            return self::FAILURE;
        }

        $this->components->info('Filling the index…');

        Exercise::makeAllSearchable();

        $this->components->info(Exercise::count().' exercises indexed.');

        return self::SUCCESS;
    }

    /**
     * Teach the engine what to match on and the French vocabulary it needs.
     */
    private function configureIndex(): bool
    {
        try {
            $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));

            $client->index(config('scout.prefix').(new Exercise)->searchableAs())->updateSettings([
                'searchableAttributes' => self::SEARCHABLE,
                'filterableAttributes' => self::FILTERABLE,
                'synonyms' => self::SYNONYMS,
                // Exercise names are short: "squat" or "bench" deserve typo
                // tolerance too, which the default of five characters denies.
                'typoTolerance' => ['minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8]],
            ]);
        } catch (Throwable $e) {
            $this->components->error("Could not reach the search engine: {$e->getMessage()}");

            return false;
        }

        $this->components->info('Index settings and French synonyms applied.');

        return true;
    }
}
