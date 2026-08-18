<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Exercise;
use App\Models\Sequence;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    /**
     * Give every user a history of trainings, each with a few activities and
     * sequences. Activity types are reused from the ones the user already owns.
     */
    public function run(): void
    {
        $exercises = Exercise::query()->inRandomOrder()->limit(6)->get();

        User::each(function (User $user) use ($exercises): void {
            if ($exercises->isEmpty()) {
                return;
            }

            Training::factory()
                ->count(30)
                ->for($user)
                ->create()
                ->each(function (Training $training) use ($exercises): void {
                    Activity::factory()
                        ->count(4)
                        ->forTraining($training)
                        // Four different exercises, as a real session would hold.
                        ->sequence(...$exercises->shuffle()->take(4)
                            ->map(fn (Exercise $exercise): array => ['exercise_id' => $exercise->id])
                            ->all())
                        ->create(['completed_at' => $training->date])
                        ->each(fn (Activity $activity) => Sequence::factory()
                            ->count(4)
                            ->for($activity)
                            ->create());

                    // History is history: past sessions are closed.
                    $training->update(['completed_at' => $training->date]);
                });
        });
    }
}
