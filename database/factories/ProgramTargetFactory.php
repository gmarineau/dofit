<?php

namespace Database\Factories;

use App\Models\ProgramItem;
use App\Models\ProgramTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramTarget>
 */
class ProgramTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_item_id' => ProgramItem::factory(),
            'position' => 0,
            'sets' => fake()->numberBetween(2, 4),
            'repetition' => fake()->numberBetween(8, 12),
            'weight' => fake()->randomFloat(1, 20, 80),
        ];
    }
}
