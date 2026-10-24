<?php

namespace Database\Factories;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Periodo>
 */
class PeriodoFactory extends Factory
{
    public function definition(): array
    {
        $anio = fake()->unique()->numberBetween(2020, 2040);

        return [
            'nombre' => "{$anio}-I",
            'fecha_inicio' => "{$anio}-03-01",
            'fecha_fin' => "{$anio}-07-31",
            'activo' => false,
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }
}
