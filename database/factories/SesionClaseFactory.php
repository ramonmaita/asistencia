<?php

namespace Database\Factories;

use App\Enums\EstadoSesion;
use App\Enums\TipoSesion;
use App\Models\Curso;
use App\Models\SesionClase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesionClase>
 */
class SesionClaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'pnf_id' => fn (array $attributes) => Curso::withoutGlobalScopes()->find($attributes['curso_id'])->pnf_id,
            'fecha' => today()->subDays(fake()->unique()->numberBetween(1, 365))->toDateString(),
            'hora_inicio' => '08:00:00',
            'hora_fin' => '10:15:00',
            'estado' => EstadoSesion::Programada,
            'tipo' => TipoSesion::Regular,
        ];
    }

    public function realizada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoSesion::Realizada,
        ]);
    }

    public function suspendida(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoSesion::Suspendida,
        ]);
    }

    public function futura(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => today()->addDays(fake()->unique()->numberBetween(1, 365))->toDateString(),
        ]);
    }
}
