<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'code' => fake()->unique()->bothify('GD-###'),
            'name' => 'Gedung '.fake()->unique()->word(),
        ];
    }

    public function university(): static
    {
        return $this->state(fn (array $attributes) => [
            'faculty_id' => null,
        ]);
    }
}
