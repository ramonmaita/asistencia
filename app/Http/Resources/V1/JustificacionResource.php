<?php

namespace App\Http\Resources\V1;

use App\Models\Justificacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Justificacion
 */
class JustificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sesion' => $this->asistencia->sesionable->descripcion(),
            'motivo' => $this->motivo,
            'soporte' => $this->soporte_nombre,
            'estado' => Enumerado::de($this->estado),
            'observacion_revision' => $this->observacion_revision,
            'solicitada_at' => $this->created_at->toIso8601String(),
            'revisada_at' => $this->revisada_at?->toIso8601String(),
        ];
    }
}
