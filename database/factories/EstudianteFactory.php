<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\Pnf;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'pnf_id' => Pnf::factory(),
            'cedula' => (string) fake()->unique()->numberBetween(25000000, 35000000),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'debe_cambiar_password' => false,
            'activo' => true,
        ];
    }
}
