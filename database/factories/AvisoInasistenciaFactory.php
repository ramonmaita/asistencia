<?php

namespace Database\Factories;

use App\Enums\NivelInasistencia;
use App\Models\AvisoInasistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvisoInasistencia>
 */
class AvisoInasistenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'pnf_id' => fn (array $attributes) => Curso::withoutGlobalScopes()->find($attributes['curso_id'])->pnf_id,
            'estudiante_id' => fn (array $attributes) => Estudiante::factory()->create(['pnf_id' => $attributes['pnf_id']]),
            'nivel' => NivelInasistencia::EnRiesgo,
            'porcentaje' => 20,
        ];
    }
}
