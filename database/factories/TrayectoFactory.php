<?php

namespace Database\Factories;

use App\Enums\ModalidadProyecto;
use App\Models\Pnf;
use App\Models\Trayecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trayecto>
 */
class TrayectoFactory extends Factory
{
    public function definition(): array
    {
        $numero = fake()->numberBetween(0, 4);

        return [
            'pnf_id' => Pnf::factory(),
            'numero' => $numero,
            'nombre' => $numero === 0 ? 'Trayecto Inicial' : "Trayecto {$numero}",
            'modalidad_proyecto' => ModalidadProyecto::Grupal,
            'min_integrantes' => 3,
            'max_integrantes' => 5,
            'umbral_inasistencia_tutorias' => 25,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'modalidad_proyecto' => ModalidadProyecto::Individual,
        ]);
    }
}
