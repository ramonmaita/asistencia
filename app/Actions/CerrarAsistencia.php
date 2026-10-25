<?php

namespace App\Actions;

use App\Enums\EstadoAsistencia;
use App\Models\Contracts\Sesionable;
use App\Models\User;

/**
 * Cierra la toma de asistencia por QR: los convocados que no se
 * registraron quedan ausentes y la sesión, realizada.
 */
class CerrarAsistencia
{
    public function __construct(private RegistrarAsistencias $registrar) {}

    /**
     * @return int Cantidad de estudiantes marcados como ausentes.
     */
    public function handle(Sesionable $sesion, User $registrador): int
    {
        $registrados = $sesion->asistencias()->pluck('estudiante_id')->all();

        $ausentes = $sesion->participantes()
            ->whereKeyNot($registrados)
            ->pluck('estudiantes.id')
            ->mapWithKeys(fn (int $id): array => [$id => EstadoAsistencia::Ausente])
            ->all();

        $this->registrar->handle($sesion, $ausentes, $registrador);

        return count($ausentes);
    }
}
