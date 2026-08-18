<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state: an entry of the shared library.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'user_id' => null,
            'slug' => Str::slug($name),
            'name' => $name,
            'category' => 'strength',
            'level' => 'beginner',
            'equipment' => 'barbell',
            'primary_muscles' => ['chest'],
            'secondary_muscles' => [],
            'instructions' => [],
        ];
    }

    /**
     * An exercise the user added themselves.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => ['user_id' => $user->id]);
    }
}
