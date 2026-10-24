<?php

namespace Database\Factories;

use App\Models\Trayecto;
use App\Models\UnidadCurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadCurricular>
 */
class UnidadCurricularFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trayecto_id' => Trayecto::factory(),
            'pnf_id' => fn (array $attributes) => Trayecto::withoutGlobalScopes()->find($attributes['trayecto_id'])->pnf_id,
            'codigo' => strtoupper(fake()->unique()->bothify('UC-###??')),
            'nombre' => ucfirst(fake()->words(3, true)),
            'horas_semanales' => fake()->numberBetween(2, 8),
            'es_proyecto' => false,
        ];
    }

    public function proyecto(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Proyecto',
            'es_proyecto' => true,
        ]);
    }
}
