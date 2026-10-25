<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Actions\CalcularInasistencias;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AsistenciaResource;
use App\Http\Resources\V1\CursoResource;
use App\Models\Curso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cursos del estudiante con su porcentaje de inasistencia y el detalle de
 * sus asistencias a clases. Un curso en el que no está inscrito da 404.
 */
class CursoController extends Controller
{
    public function index(Request $request, CalcularInasistencias $calcular): AnonymousResourceCollection
    {
        $estudiante = $request->user();

        $cursos = Curso::query()
            ->cursadosPor($estudiante)
            ->with(['unidadCurricular', 'seccion.periodo', 'docentes', 'pnf'])
            ->orderByDesc('seccion_id')
            ->get();

        return CursoResource::collection(
            $cursos->map(fn (Curso $curso): CursoResource => (new CursoResource($curso))->conResumen($calcular->handle($curso, $estudiante)->first())),
        );
    }

    public function asistencias(Request $request, int $curso): AnonymousResourceCollection
    {
        $curso = Curso::query()->cursadosPor($request->user())->findOrFail($curso);

        return AsistenciaResource::collection(
            $curso->asistencias()
                ->whereBelongsTo($request->user())
                ->with(['sesionable', 'justificaciones' => fn ($justificaciones) => $justificaciones->latest()])
                ->orderByDesc('sesiones_clase.fecha')
                ->orderByDesc('sesiones_clase.hora_inicio')
                ->get(),
        );
    }
}
