<?php

namespace Database\Factories;

use App\Models\Metric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Metric>
 */
class MetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => 'weight',
            'value' => (string) fake()->randomFloat(1, 60, 95),
            'date' => fake()->dateTimeBetween('-6 months'),
        ];
    }
}
