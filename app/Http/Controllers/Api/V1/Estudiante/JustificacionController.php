<?php

namespace App\Http\Controllers\Api\V1\Estudiante;

use App\Actions\SolicitarJustificacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SolicitarJustificacionRequest;
use App\Http\Resources\V1\JustificacionResource;
use App\Models\Justificacion;
use App\Models\SesionClase;
use App\Models\Tutoria;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Justificaciones del estudiante: las consulta y solicita una nueva sobre
 * una inasistencia propia (de clase o de tutoría), con su soporte.
 */
class JustificacionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return JustificacionResource::collection(
            $request->user()->justificaciones()
                ->with(['asistencia.sesionable' => fn (MorphTo $sesionable) => $sesionable->morphWith([
                    SesionClase::class => ['curso.unidadCurricular'],
                    Tutoria::class => ['proyecto'],
                ])])
                ->latest()
                ->get(),
        );
    }

    public function store(SolicitarJustificacionRequest $request, int $asistencia, SolicitarJustificacion $solicitar): JustificacionResource
    {
        $estudiante = $request->user();
        $asistencia = $estudiante->asistencias()->findOrFail($asistencia);
        $ruta = $request->file('soporte')->store(Justificacion::DIRECTORIO, Justificacion::DISCO);

        try {
            $justificacion = $solicitar->handle($estudiante, $asistencia, $request->validated('motivo'), $ruta, $request->file('soporte')->getClientOriginalName());
        } catch (Throwable $error) {
            Storage::disk(Justificacion::DISCO)->delete($ruta);

            throw $error;
        }

        return new JustificacionResource($justificacion->load('asistencia.sesionable'));
    }
}
