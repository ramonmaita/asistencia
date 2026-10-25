<?php

namespace App\Support;

use App\Enums\EstadoAsistencia;
use App\Models\Contracts\Sesionable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Horario en que se acepta el registro por QR de una sesión: el mismo día,
 * desde unos minutos antes del inicio hasta la hora de fin. Pasada la
 * tolerancia desde el inicio, el estudiante queda con tardanza.
 */
class VentanaDeAsistencia
{
    public function abre(Sesionable $sesion): Carbon
    {
        return $sesion->inicio()->subMinutes((int) config('asistencia.ventana.minutos_antes'));
    }

    public function estaAbierta(Sesionable $sesion, ?Carbon $ahora = null): bool
    {
        $ahora ??= now();

        return $ahora->betweenIncluded($this->abre($sesion), $sesion->fin());
    }

    /**
     * @throws ValidationException
     */
    public function exigirAbierta(Sesionable $sesion, string $campo = 'codigo'): void
    {
        if ($this->estaAbierta($sesion)) {
            return;
        }

        throw ValidationException::withMessages([
            $campo => now()->lt($this->abre($sesion))
                ? 'La asistencia de esta sesión aún no está abierta (abre a las '.$this->abre($sesion)->format('h:i a').').'
                : 'La sesión ya terminó; pide a tu docente que registre la asistencia manualmente.',
        ]);
    }

    public function estadoSegunHora(Sesionable $sesion, ?Carbon $ahora = null): EstadoAsistencia
    {
        $limite = $sesion->inicio()->addMinutes((int) config('asistencia.ventana.tolerancia_tardanza'));

        return ($ahora ?? now())->gt($limite) ? EstadoAsistencia::Tardanza : EstadoAsistencia::Presente;
    }
}
