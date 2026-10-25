<?php

namespace App\Http\Controllers\Api\V1\Docente;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CursoResource;
use App\Http\Resources\V1\SesionResource;
use App\Models\Curso;
use App\Models\Pnf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Cursos que dicta el docente en el PNF y sus sesiones.
 */
class CursoController extends Controller
{
    public function index(Request $request, Pnf $pnf): AnonymousResourceCollection
    {
        return CursoResource::collection(
            Curso::query()
                ->dictadosPor($request->user())
                ->delPeriodoActivo()
                ->with(['unidadCurricular', 'seccion.periodo', 'docentes', 'pnf'])
                ->get(),
        );
    }

    public function sesiones(Request $request, Pnf $pnf, int $curso): AnonymousResourceCollection
    {
        $curso = Curso::query()->dictadosPor($request->user())->findOrFail($curso);

        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        return SesionResource::collection(
            $curso->sesiones()
                ->with(['curso.unidadCurricular', 'curso.seccion'])
                ->withCount('asistencias')
                ->when($request->date('desde'), fn ($query, $desde) => $query->whereDate('fecha', '>=', $desde))
                ->when($request->date('hasta'), fn ($query, $hasta) => $query->whereDate('fecha', '<=', $hasta))
                ->orderBy('fecha')
                ->orderBy('hora_inicio')
                ->get(),
        );
    }
}
