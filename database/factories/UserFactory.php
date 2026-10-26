<?php

namespace Database\Factories;

use App\Enums\RolPnf;
use App\Models\Pnf;
use App\Models\User;
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
            'cedula' => (string) fake()->unique()->numberBetween(5000000, 32000000),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function coordinadorDe(Pnf $pnf): static
    {
        return $this->afterCreating(fn (User $user) => $user->pnfs()->attach($pnf, ['rol' => RolPnf::Coordinador]));
    }

    public function docenteDe(Pnf $pnf): static
    {
        return $this->afterCreating(fn (User $user) => $user->pnfs()->attach($pnf, ['rol' => RolPnf::Docente]));
    }

    public function administrativoDe(Pnf $pnf): static
    {
        return $this->afterCreating(fn (User $user) => $user->pnfs()->attach($pnf, ['rol' => RolPnf::Administrativo]));
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_superadmin' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
