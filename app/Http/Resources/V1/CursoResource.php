<?php

namespace App\Http\Resources\V1;

use App\Actions\ResumenInasistencia;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Curso (unidad curricular dictada en una sección). Para el estudiante
 * incluye su resumen de asistencia si se pasa con conResumen().
 *
 * @mixin Curso
 */
class CursoResource extends JsonResource
{
    private ?ResumenInasistencia $resumen = null;

    public function conResumen(?ResumenInasistencia $resumen): static
    {
        $this->resumen = $resumen;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unidad_curricular' => [
                'codigo' => $this->unidadCurricular->codigo,
                'nombre' => $this->unidadCurricular->nombre,
                'es_proyecto' => $this->unidadCurricular->es_proyecto,
            ],
            'seccion' => $this->seccion->codigo,
            'periodo' => $this->seccion->periodo->nombre,
            'docentes' => $this->docentes->pluck('name')->all(),
            'umbral_inasistencia' => (float) $this->pnf->umbral_inasistencia_clases,
            'resumen' => $this->when($this->resumen !== null, fn () => new ResumenResource($this->resumen)),
        ];
    }
}
