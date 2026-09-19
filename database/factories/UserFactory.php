<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => Status::ACCOUNT_PENDING,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => Status::ACCOUNT_REJECTED,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_status' => Status::ACCOUNT_INACTIVE,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Status::ROLE_ADMIN,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);
    }

    public function officer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Status::ROLE_OFFICER,
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);
    }
}
