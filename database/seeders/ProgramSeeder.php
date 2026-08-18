<?php

namespace Database\Seeders;

use App\Models\ActivityType;
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
        User::with('activityTypes')->each(function (User $user): void {
            if ($user->activityTypes->isEmpty() || $user->programs()->exists()) {
                return;
            }

            $program = $user->programs()->create(['name' => 'Haut du corps']);

            $user->activityTypes
                ->take(3)
                ->values()
                ->each(fn (ActivityType $activityType, int $position) => $program->items()->create([
                    'activity_type_id' => $activityType->id,
                    'position' => $position,
                    'target_sets' => 4,
                    'target_reps' => 10,
                ]));
        });
    }
}
