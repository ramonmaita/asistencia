<?php

namespace App\Actions;

use App\Filament\App\Resources\Justificaciones\JustificacionResource;
use App\Models\Asistencia;
use App\Models\Estudiante;
use App\Models\Justificacion;
use App\Models\SesionClase;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Registra la solicitud de justificación de una inasistencia propia del
 * estudiante y avisa a los docentes del curso.
 */
class SolicitarJustificacion
{
    public function handle(Estudiante $estudiante, Asistencia $asistencia, string $motivo, string $soportePath, string $soporteNombre): Justificacion
    {
        if ($asistencia->estudiante_id !== $estudiante->getKey() || ! $asistencia->puedeJustificarse()) {
            throw ValidationException::withMessages([
                'motivo' => 'Esta inasistencia no se puede justificar.',
            ]);
        }

        $justificacion = $asistencia->justificaciones()->create([
            'pnf_id' => $asistencia->pnf_id,
            'estudiante_id' => $estudiante->getKey(),
            'motivo' => $motivo,
            'soporte_path' => $soportePath,
            'soporte_nombre' => $soporteNombre,
        ]);

        $sesion = $asistencia->sesionable;

        if ($sesion instanceof SesionClase) {
            Notification::make()
                ->title('Nueva justificación por revisar')
                ->body("{$estudiante->getFilamentName()} · {$sesion->descripcion()}")
                ->icon(Heroicon::OutlinedDocumentCheck)
                ->actions([
                    Action::make('revisar')
                        ->label('Revisar')
                        ->url(JustificacionResource::getUrl(panel: 'app', tenant: $sesion->curso->pnf)),
                ])
                ->sendToDatabase($sesion->curso->docentes);
        }

        return $justificacion;
    }
}
