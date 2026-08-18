<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseSeeder extends Seeder
{
    /**
     * A handful of exercises to work with when the shared library has not been
     * imported. `dofit:import-exercises` brings in the real catalogue.
     *
     * @var list<string>
     */
    public const array STARTERS = ['Chest Press', 'Pull Down', 'Biceps Curl', 'Rear Delt Fly'];

    /**
     * Seed the exercises, leaving an imported library alone.
     */
    public function run(): void
    {
        if (Exercise::query()->exists()) {
            return;
        }

        foreach (self::STARTERS as $name) {
            Exercise::factory()->create(['name' => $name, 'slug' => Str::slug($name)]);
        }
    }
}
