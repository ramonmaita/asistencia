<?php

namespace App\Http\Resources\V1;

use App\Models\Asistencia;
use App\Models\Tutoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Asistencia del estudiante a una sesión (vista del propio estudiante).
 *
 * @mixin Asistencia
 */
class AsistenciaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sesion = $this->sesionable;
        $justificacion = $this->relationLoaded('justificaciones') ? $this->justificaciones->first() : null;

        return [
            'id' => $this->id,
            'estado' => Enumerado::de($this->estado),
            'metodo' => Enumerado::de($this->metodo),
            'registrado_at' => $this->registrado_at?->toIso8601String(),
            'sesion' => [
                'tipo' => $sesion instanceof Tutoria ? 'tutoria' : 'clase',
                'fecha' => $sesion->fecha->toDateString(),
                'horario' => $sesion->horario(),
                'tema' => $sesion instanceof Tutoria ? $sesion->tema : null,
            ],
            'justificacion' => $justificacion ? Enumerado::de($justificacion->estado) : null,
            'puede_justificarse' => $this->puedeJustificarse(),
        ];
    }
}
