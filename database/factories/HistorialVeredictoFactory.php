<?php

namespace Database\Factories;

use App\Enums\VeredictoProyecto;
use App\Models\HistorialVeredicto;
use App\Models\IntegranteProyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistorialVeredicto>
 */
class HistorialVeredictoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'integrante_proyecto_id' => IntegranteProyecto::factory(),
            'pnf_id' => fn (array $attributes) => IntegranteProyecto::withoutGlobalScopes()->find($attributes['integrante_proyecto_id'])->pnf_id,
            'veredicto' => VeredictoProyecto::Aprobado,
        ];
    }
}
