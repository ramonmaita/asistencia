<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Actions\CalcularInasistencias;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AsistenciaResource;
use App\Http\Resources\V1\ProyectoResource;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Proyectos del estudiante con su asistencia a tutorías y su veredicto.
 */
class ProyectoController extends Controller
{
    public function index(Request $request, CalcularInasistencias $calcular): AnonymousResourceCollection
    {
        $estudiante = $request->user();

        $proyectos = Proyecto::query()
            ->conIntegrante($estudiante)
            ->with(['curso.unidadCurricular', 'curso.seccion.periodo', 'curso.seccion.trayecto', 'curso.docentes', 'tutorTecnico', 'integrantes.estudiante'])
            ->latest('id')
            ->get();

        return ProyectoResource::collection(
            $proyectos->map(fn (Proyecto $proyecto): ProyectoResource => (new ProyectoResource($proyecto))->conResumen($calcular->deProyecto($proyecto, $estudiante)->first())),
        );
    }

    public function tutorias(Request $request, int $proyecto): AnonymousResourceCollection
    {
        $proyecto = Proyecto::query()->conIntegrante($request->user())->findOrFail($proyecto);

        return AsistenciaResource::collection(
            $proyecto->asistencias()
                ->whereBelongsTo($request->user())
                ->with(['sesionable', 'justificaciones' => fn ($justificaciones) => $justificaciones->latest()])
                ->orderByDesc('tutorias.fecha')
                ->orderByDesc('tutorias.hora_inicio')
                ->get(),
        );
    }
}
