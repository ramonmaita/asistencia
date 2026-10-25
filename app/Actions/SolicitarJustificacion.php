<?php

namespace App\Actions;

use App\Filament\App\Resources\Justificaciones\JustificacionResource;
use App\Models\Asistencia;
use App\Models\Contracts\Sesionable;
use App\Models\Estudiante;
use App\Models\Justificacion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Registra la solicitud de justificación de una inasistencia propia del
 * estudiante (de una clase o de una tutoría) y avisa a los docentes a cargo.
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

        /** @var Sesionable $sesion */
        $sesion = $asistencia->sesionable;

        Notification::make()
            ->title('Nueva justificación por revisar')
            ->body("{$estudiante->getFilamentName()} · {$sesion->descripcion()}")
            ->icon(Heroicon::OutlinedDocumentCheck)
            ->actions([
                Action::make('revisar')
                    ->label('Revisar')
                    ->url(JustificacionResource::getUrl(panel: 'app', tenant: $estudiante->pnf)),
            ])
            ->sendToDatabase($sesion->responsables());

        return $justificacion;
    }
}
