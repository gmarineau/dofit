<?php

namespace Database\Factories;

use App\Models\ActivityType;
use App\Models\Program;
use App\Models\ProgramItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramItem>
 */
class ProgramItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'activity_type_id' => ActivityType::factory(),
            'position' => 0,
            'target_sets' => fake()->numberBetween(3, 5),
            'target_reps' => fake()->numberBetween(8, 12),
            'target_weight' => fake()->randomFloat(1, 20, 80),
        ];
    }
}
