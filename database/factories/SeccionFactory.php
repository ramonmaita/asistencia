<?php

namespace Database\Factories;

use App\Enums\Turno;
use App\Models\Periodo;
use App\Models\Seccion;
use App\Models\Trayecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seccion>
 */
class SeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trayecto_id' => Trayecto::factory(),
            'pnf_id' => fn (array $attributes) => Trayecto::withoutGlobalScopes()->find($attributes['trayecto_id'])->pnf_id,
            'periodo_id' => Periodo::factory(),
            'codigo' => fake()->unique()->bothify('S-##'),
            'turno' => fake()->randomElement(Turno::cases()),
        ];
    }
}
