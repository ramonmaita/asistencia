<?php

namespace Database\Factories;

use App\Enums\EstadoSesion;
use App\Models\Proyecto;
use App\Models\Tutoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tutoria>
 */
class TutoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'pnf_id' => fn (array $attributes) => Proyecto::withoutGlobalScopes()->find($attributes['proyecto_id'])->pnf_id,
            'fecha' => today()->subDays(fake()->numberBetween(1, 90))->toDateString(),
            'hora_inicio' => '14:00:00',
            'hora_fin' => '15:30:00',
            'tema' => 'Revisión del '.fake()->randomElement(['diagnóstico', 'plan de acción', 'prototipo', 'informe']),
            'estado' => EstadoSesion::Programada,
        ];
    }

    public function realizada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoSesion::Realizada,
        ]);
    }

    public function futura(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => today()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        ]);
    }
}
