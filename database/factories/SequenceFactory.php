<?php

namespace Database\Factories;

use App\Enums\SequenceUnit;
use App\Models\Activity;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sequence>
 */
class SequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'repetition' => fake()->numberBetween(5, 12),
            'weight' => fake()->randomFloat(1, 10, 120),
            'unit' => SequenceUnit::Kg,
        ];
    }

    /**
     * Indicate that the sequence was performed without any weight.
     */
    public function bodyweight(): static
    {
        return $this->state(fn (array $attributes): array => [
            'weight' => null,
        ]);
    }
}
