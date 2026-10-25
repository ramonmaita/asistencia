<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\IntegranteProyecto;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * El estudiante queda inscrito en el curso del proyecto.
 *
 * @extends Factory<IntegranteProyecto>
 */
class IntegranteProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'curso_id' => fn (array $attributes) => Proyecto::withoutGlobalScopes()->find($attributes['proyecto_id'])->curso_id,
            'pnf_id' => fn (array $attributes) => Proyecto::withoutGlobalScopes()->find($attributes['proyecto_id'])->pnf_id,
            'estudiante_id' => fn (array $attributes) => Estudiante::factory()->create(['pnf_id' => $attributes['pnf_id']]),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (IntegranteProyecto $integrante) {
            $integrante->proyecto()->withoutGlobalScopes()->first()->curso()->withoutGlobalScopes()->first()
                ->estudiantes()->syncWithoutDetaching([$integrante->estudiante_id]);
        });
    }
}
