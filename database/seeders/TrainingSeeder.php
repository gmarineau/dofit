<?php

namespace Database\Seeders;

use App\Models\Activity;
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
        User::with('activityTypes')->each(function (User $user): void {
            if ($user->activityTypes->isEmpty()) {
                return;
            }

            Training::factory()
                ->count(30)
                ->for($user)
                ->create()
                ->each(function (Training $training): void {
                    Activity::factory()
                        ->count(4)
                        ->forTraining($training)
                        ->create()
                        ->each(fn (Activity $activity) => Sequence::factory()
                            ->count(4)
                            ->for($activity)
                            ->create());
                });
        });
    }
}
