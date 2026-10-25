<?php

namespace App\Http\Resources\V1;

use App\Models\Contracts\Sesionable;
use App\Models\SesionClase;
use App\Models\Tutoria;
use App\Support\VentanaDeAsistencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sesión de clase o tutoría con el mismo formato. "tipo" e "id" identifican
 * la sesión en las rutas /sesiones/{tipo}/{id}.
 *
 * @mixin SesionClase|Tutoria
 */
class SesionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Sesionable $sesion */
        $sesion = $this->resource;
        $esTutoria = $sesion instanceof Tutoria;
        $curso = $esTutoria ? $sesion->proyecto->curso : $sesion->curso;

        return [
            'tipo' => $esTutoria ? 'tutoria' : 'clase',
            'id' => $sesion->getKey(),
            'fecha' => $sesion->fecha->toDateString(),
            'hora_inicio' => substr($sesion->hora_inicio, 0, 5),
            'hora_fin' => substr($sesion->hora_fin, 0, 5),
            'estado' => Enumerado::de($sesion->estado),
            'titulo' => $esTutoria ? $sesion->tema : $curso->unidadCurricular->nombre,
            'detalle' => $esTutoria
                ? "Tutoría · {$sesion->proyecto->titulo}"
                : "{$curso->seccion->codigo} · {$curso->unidadCurricular->codigo}",
            'curso_id' => $curso->getKey(),
            'convocados' => $sesion->participantes()->count(),
            'registrados' => $this->asistencias_count ?? $sesion->asistencias()->count(),
            'asistencia_abierta' => app(VentanaDeAsistencia::class)->estaAbierta($sesion),
            'puede_registrar' => (bool) $request->user()?->can('registrarAsistencia', $sesion),
        ];
    }
}
