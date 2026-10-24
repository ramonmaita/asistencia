<?php

namespace Database\Factories;

use App\Enums\EstadoAsistencia;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\SesionClase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sesionable_type' => (new SesionClase)->getMorphClass(),
            'sesionable_id' => SesionClase::factory()->realizada(),
            'pnf_id' => fn (array $attributes) => SesionClase::withoutGlobalScopes()->find($attributes['sesionable_id'])->pnf_id,
            'estudiante_id' => fn (array $attributes) => Estudiante::factory()->create(['pnf_id' => $attributes['pnf_id']]),
            'estado' => EstadoAsistencia::Presente,
            'metodo' => MetodoAsistencia::Manual,
            'registrado_at' => now(),
        ];
    }

    public function ausente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoAsistencia::Ausente,
        ]);
    }

    public function tardanza(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoAsistencia::Tardanza,
        ]);
    }

    public function justificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoAsistencia::Justificado,
        ]);
    }
}
