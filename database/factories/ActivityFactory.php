<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Exercise;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'exercise_id' => Exercise::factory(),
        ];
    }

    /**
     * Attach the activity to a training.
     */
    public function forTraining(Training $training): static
    {
        return $this->state(fn (array $attributes): array => [
            'training_id' => $training->id,
        ]);
    }
}
