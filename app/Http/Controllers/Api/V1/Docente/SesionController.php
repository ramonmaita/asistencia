<?php

namespace App\Http\Controllers\Api\V1\Docente;

use App\Actions\CerrarAsistencia;
use App\Actions\RegistrarAsistenciaPorQr;
use App\Actions\RegistrarAsistencias;
use App\Enums\MetodoAsistencia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EscanearCarnetRequest;
use App\Http\Requests\Api\V1\RegistrarAsistenciasRequest;
use App\Http\Resources\V1\Enumerado;
use App\Http\Resources\V1\SesionResource;
use App\Models\Contracts\Sesionable;
use App\Models\Estudiante;
use App\Models\Pnf;
use App\Models\SesionClase;
use App\Models\Tutoria;
use App\Support\CodigoQrDinamico;
use App\Support\VentanaDeAsistencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Toma de asistencia desde la app del docente: clases que dicta y tutorías
 * de los proyectos que tutora. Una sesión ajena da 404.
 */
class SesionController extends Controller
{
    /**
     * Clases y tutorías de hoy del docente, por hora de inicio.
     */
    public function hoy(Request $request, Pnf $pnf): AnonymousResourceCollection
    {
        $docente = $request->user();

        $clases = SesionClase::query()
            ->whereDate('fecha', today())
            ->whereHas('curso', fn (Builder $cursos) => $cursos->dictadosPor($docente))
            ->with(['curso.unidadCurricular', 'curso.seccion'])
            ->withCount('asistencias')
            ->get();

        $tutorias = Tutoria::query()
            ->whereDate('fecha', today())
            ->whereHas('proyecto', fn (Builder $proyectos) => $proyectos->tutoradosPor($docente))
            ->with(['proyecto.curso'])
            ->withCount('asistencias')
            ->get();

        return SesionResource::collection($clases->toBase()->merge($tutorias)->sortBy('hora_inicio')->values());
    }

    /**
     * Detalle de la sesión con la lista de convocados y su estado actual.
     */
    public function show(Pnf $pnf, string $tipo, int $sesion): JsonResponse
    {
        $sesion = $this->sesion($tipo, $sesion);
        $registradas = $sesion->asistencias()->get()->keyBy('estudiante_id');

        return response()->json([
            'data' => [
                ...(new SesionResource($sesion))->resolve(),
                'participantes' => $sesion->participantes()->orderBy('apellidos')->orderBy('nombres')->get()
                    ->map(fn (Estudiante $estudiante): array => [
                        'id' => $estudiante->id,
                        'cedula' => $estudiante->cedula,
                        'nombre' => $estudiante->nombre_completo,
                        'estado' => Enumerado::de($registradas->get($estudiante->id)?->estado),
                        'metodo' => Enumerado::de($registradas->get($estudiante->id)?->metodo),
                    ]),
            ],
        ]);
    }

    /**
     * Código del QR dinámico vigente; la app debe pedirlo de nuevo al expirar.
     */
    public function qrDinamico(Pnf $pnf, string $tipo, int $sesion, CodigoQrDinamico $codigos, VentanaDeAsistencia $ventana): JsonResponse
    {
        $sesion = $this->sesion($tipo, $sesion);
        Gate::authorize('registrarAsistencia', $sesion);
        $ventana->exigirAbierta($sesion, 'sesion');

        $codigo = $codigos->generar($sesion);

        return response()->json([
            'codigo' => $codigo['codigo'],
            'expira_at' => $codigo['expira_at']->toIso8601String(),
            'segundos' => $codigo['segundos'],
        ]);
    }

    /**
     * El docente escanea el QR del carnet de un estudiante.
     */
    public function escanear(EscanearCarnetRequest $request, Pnf $pnf, string $tipo, int $sesion, RegistrarAsistenciaPorQr $registrar): JsonResponse
    {
        $sesion = $this->sesion($tipo, $sesion);
        Gate::authorize('registrarAsistencia', $sesion);

        $estudiante = Estudiante::delCarnet($request->string('qr'));

        if (! $estudiante) {
            throw ValidationException::withMessages(['qr' => 'El carnet no es válido o no pertenece a este PNF.']);
        }

        $resultado = $registrar->handle($sesion, $estudiante, MetodoAsistencia::QrDocente, $request->user(), campo: 'qr');

        return response()->json([
            'message' => $resultado['nueva'] ? 'Asistencia registrada.' : 'El estudiante ya tenía su asistencia registrada.',
            'estudiante' => ['id' => $estudiante->id, 'cedula' => $estudiante->cedula, 'nombre' => $estudiante->nombre_completo],
            'estado' => Enumerado::de($resultado['asistencia']->estado),
        ], $resultado['nueva'] ? 201 : 200);
    }

    /**
     * Registro manual de uno o varios estudiantes.
     */
    public function registrar(RegistrarAsistenciasRequest $request, Pnf $pnf, string $tipo, int $sesion, RegistrarAsistencias $registrar): JsonResponse
    {
        $sesion = $this->sesion($tipo, $sesion);
        Gate::authorize('registrarAsistencia', $sesion);

        try {
            $registrar->handle($sesion, $request->estados(), $request->user());
        } catch (InvalidArgumentException $error) {
            throw ValidationException::withMessages(['estados' => $error->getMessage()]);
        }

        return response()->json(['message' => 'Asistencia guardada.']);
    }

    /**
     * Cierra la toma por QR: quienes no se registraron quedan ausentes.
     */
    public function cerrar(Request $request, Pnf $pnf, string $tipo, int $sesion, CerrarAsistencia $cerrar): JsonResponse
    {
        $sesion = $this->sesion($tipo, $sesion);
        Gate::authorize('registrarAsistencia', $sesion);

        $ausentes = $cerrar->handle($sesion, $request->user());

        return response()->json(['message' => "Asistencia cerrada. Ausentes: {$ausentes}.", 'ausentes' => $ausentes]);
    }

    /**
     * La sesión del PNF actual que el docente gestiona; si no, 404.
     */
    private function sesion(string $tipo, int $id): Sesionable
    {
        $sesion = ($tipo === 'tutoria' ? Tutoria::class : SesionClase::class)::find($id);

        abort_unless($sesion && Gate::allows('view', $sesion), 404);

        return $sesion;
    }
}
