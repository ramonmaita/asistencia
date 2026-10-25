<?php

namespace App\Http\Resources\V1;

use App\Actions\ResumenInasistencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Asistencia acumulada del estudiante (a clases o a tutorías).
 *
 * @mixin ResumenInasistencia
 */
class ResumenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sesiones' => $this->sesiones,
            'asistencias' => $this->asistencias(),
            'tardanzas' => $this->tardanzas,
            'inasistencias' => $this->ausentes,
            'justificadas' => $this->justificadas,
            'porcentaje' => $this->porcentaje,
            'nivel' => Enumerado::de($this->nivel),
        ];
    }
}
