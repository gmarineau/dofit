<?php

namespace Database\Factories;

use App\Models\Exercise;
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
            'exercise_id' => Exercise::factory(),
            'position' => 0,
        ];
    }
}
