<?php

namespace Database\Factories;

use App\Models\Pnf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pnf>
 */
class PnfFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'PNF en '.fake()->unique()->words(2, true),
            'codigo' => strtoupper(fake()->unique()->bothify('PNF-???')),
            'umbral_inasistencia_clases' => 25,
            'activo' => true,
        ];
    }
}
