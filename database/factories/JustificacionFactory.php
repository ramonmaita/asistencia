<?php

namespace Database\Factories;

use App\Models\Asistencia;
use App\Models\Justificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Justificacion>
 */
class JustificacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asistencia_id' => Asistencia::factory()->ausente(),
            'pnf_id' => fn (array $attributes) => Asistencia::withoutGlobalScopes()->find($attributes['asistencia_id'])->pnf_id,
            'estudiante_id' => fn (array $attributes) => Asistencia::withoutGlobalScopes()->find($attributes['asistencia_id'])->estudiante_id,
            'motivo' => fake()->sentence(),
            'soporte_path' => Justificacion::DIRECTORIO.'/'.fake()->uuid().'.pdf',
            'soporte_nombre' => 'reposo_medico.pdf',
        ];
    }
}
