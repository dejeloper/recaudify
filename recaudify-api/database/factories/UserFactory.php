<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName  = fake()->lastName();

        return [
            'name'     => "{$firstName} {$lastName}",
            'username' => fake()->unique()->userName(),
            'email'    => fake()->optional(0.6)->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role));
    }
}
