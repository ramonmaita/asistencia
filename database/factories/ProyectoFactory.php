<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\UnidadCurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'curso_id' => fn () => Curso::factory()->create([
                'unidad_curricular_id' => fn (array $curso) => UnidadCurricular::factory()->create([
                    'trayecto_id' => Seccion::withoutGlobalScopes()->find($curso['seccion_id'])->trayecto_id,
                    'es_proyecto' => true,
                ]),
            ]),
            'pnf_id' => fn (array $attributes) => Curso::withoutGlobalScopes()->find($attributes['curso_id'])->pnf_id,
            'titulo' => 'Sistema de '.fake()->words(3, true),
            'comunidad' => 'Consejo comunal '.fake()->lastName(),
            'tutor_comunitario' => fake()->name(),
        ];
    }
}
