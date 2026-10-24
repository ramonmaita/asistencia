<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Seccion;
use App\Models\UnidadCurricular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seccion_id' => Seccion::factory(),
            'pnf_id' => fn (array $attributes) => Seccion::withoutGlobalScopes()->find($attributes['seccion_id'])->pnf_id,
            'unidad_curricular_id' => fn (array $attributes) => UnidadCurricular::factory()->create([
                'trayecto_id' => Seccion::withoutGlobalScopes()->find($attributes['seccion_id'])->trayecto_id,
            ]),
        ];
    }
}
