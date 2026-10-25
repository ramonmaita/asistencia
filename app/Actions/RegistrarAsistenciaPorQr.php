<?php

namespace App\Actions;

use App\Enums\EstadoAsistencia;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Contracts\Sesionable;
use App\Models\Estudiante;
use App\Models\User;
use App\Support\VentanaDeAsistencia;
use Illuminate\Validation\ValidationException;

/**
 * Registra la asistencia de un estudiante por QR: el docente escanea el
 * carnet (qr_docente) o el estudiante escanea el QR dinámico (qr_dinamico).
 *
 * Solo se acepta durante la ventana de la sesión; pasada la tolerancia
 * queda como tardanza. Con QR dinámico, si la sede tiene coordenadas, el
 * estudiante debe estar dentro de su radio. Un registro presente, con
 * tardanza o justificado no se modifica; uno ausente pasa al nuevo estado.
 */
class RegistrarAsistenciaPorQr
{
    public function __construct(
        private RegistrarAsistencias $registrar,
        private VentanaDeAsistencia $ventana,
    ) {}

    /**
     * @return array{asistencia: Asistencia, nueva: bool}
     *
     * @throws ValidationException
     */
    public function handle(
        Sesionable $sesion,
        Estudiante $estudiante,
        MetodoAsistencia $metodo,
        ?User $registrador = null,
        ?float $latitud = null,
        ?float $longitud = null,
        string $campo = 'codigo',
    ): array {
        if (! $sesion->participantes()->whereKey($estudiante->getKey())->exists()) {
            throw ValidationException::withMessages([$campo => "{$estudiante->getFilamentName()} no está convocado a esta sesión."]);
        }

        $this->ventana->exigirAbierta($sesion, $campo);

        if ($metodo === MetodoAsistencia::QrDinamico) {
            $this->exigirUbicacion($sesion, $latitud, $longitud);
        }

        $actual = $sesion->asistencias()->whereBelongsTo($estudiante)->first();

        if ($actual && $actual->estado !== EstadoAsistencia::Ausente) {
            return ['asistencia' => $actual, 'nueva' => false];
        }

        $this->registrar->handle($sesion, [$estudiante->getKey() => $this->ventana->estadoSegunHora($sesion)], $registrador, $metodo);

        return ['asistencia' => $sesion->asistencias()->whereBelongsTo($estudiante)->sole(), 'nueva' => true];
    }

    private function exigirUbicacion(Sesionable $sesion, ?float $latitud, ?float $longitud): void
    {
        $sede = $sesion->ubicacion();

        if (! $sede?->tieneUbicacion()) {
            return;
        }

        if ($latitud === null || $longitud === null) {
            throw ValidationException::withMessages(['latitud' => 'Activa la ubicación del teléfono para registrar tu asistencia.']);
        }

        if ($sede->distanciaA($latitud, $longitud) > $sede->radio()) {
            throw ValidationException::withMessages(['latitud' => "Debes estar en la sede {$sede->nombre} para registrar tu asistencia."]);
        }
    }
}
