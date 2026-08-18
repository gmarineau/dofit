<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Give every user a starting program built from the activity types they
     * already own.
     */
    public function run(): void
    {
        $exercises = Exercise::query()->orderBy('id')->limit(3)->get();

        User::each(function (User $user) use ($exercises): void {
            if ($exercises->isEmpty() || $user->programs()->exists()) {
                return;
            }

            $program = $user->programs()->create(['name' => 'Haut du corps']);

            $exercises
                ->values()
                ->each(function (Exercise $exercise, int $position) use ($program): void {
                    $item = $program->items()->create([
                        'exercise_id' => $exercise->id,
                        'position' => $position,
                    ]);

                    // Two blocks: a couple of sets to warm into the load, then
                    // the same reps a notch heavier.
                    foreach ([[0, 60.0], [1, 70.0]] as [$blockPosition, $weight]) {
                        $item->targets()->create([
                            'position' => $blockPosition,
                            'sets' => 2,
                            'repetition' => 10,
                            'weight' => $weight,
                        ]);
                    }
                });
        });
    }
}
