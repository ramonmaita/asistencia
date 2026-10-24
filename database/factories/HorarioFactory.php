<?php

namespace Database\Factories;

use App\Enums\DiaSemana;
use App\Models\Curso;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
class HorarioFactory extends Factory
{
    public function definition(): array
    {
        $inicio = fake()->numberBetween(7, 18);

        return [
            'curso_id' => Curso::factory(),
            'dia_semana' => fake()->randomElement(array_slice(DiaSemana::cases(), 0, 5)),
            'hora_inicio' => sprintf('%02d:00', $inicio),
            'hora_fin' => sprintf('%02d:30', $inicio + 1),
            'aula' => fake()->bothify('Aula ##'),
        ];
    }
}
