<?php

namespace App\Http\Resources\V1;

use App\Actions\ResumenInasistencia;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proyecto visto por uno de sus integrantes: tutores, integrantes, su
 * asistencia a tutorías y su veredicto.
 *
 * @mixin Proyecto
 */
class ProyectoResource extends JsonResource
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
        $integrante = $this->integrantes->firstWhere('estudiante_id', $request->user()->getKey());

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'comunidad' => $this->comunidad,
            'unidad_curricular' => $this->curso->unidadCurricular->nombre,
            'seccion' => $this->curso->seccion->codigo,
            'periodo' => $this->curso->seccion->periodo->nombre,
            'tutores' => [
                'academicos' => $this->curso->docentes->pluck('name')->all(),
                'tecnico' => $this->tutorTecnico?->name,
                'institucional' => $this->tutor_institucional ? [
                    'nombre' => $this->tutor_institucional,
                    'contacto' => $this->tutor_institucional_contacto,
                ] : null,
            ],
            'integrantes' => $this->integrantes->map(fn ($integrante): string => $integrante->estudiante->nombre_completo)->sort()->values()->all(),
            'umbral_inasistencia_tutorias' => (float) $this->curso->seccion->trayecto->umbral_inasistencia_tutorias,
            'resumen' => $this->when($this->resumen !== null, fn () => new ResumenResource($this->resumen)),
            'veredicto' => Enumerado::de($integrante?->veredicto),
        ];
    }
}
