<?php

namespace App\Actions;

use App\Enums\EstadoAsistencia;
use App\Enums\NivelInasistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\SesionClase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Calcula el porcentaje de inasistencia de cada estudiante de un curso.
 *
 * Solo cuentan las sesiones de clase realizadas en las que el estudiante
 * tiene asistencia registrada (las tutorías del proyecto se controlan
 * aparte). Porcentaje = ausencias sin justificar / sesiones registradas.
 * El nivel compara ese porcentaje con el umbral del PNF.
 */
class CalcularInasistencias
{
    /**
     * @return Collection<int, ResumenInasistencia> Resúmenes por id de estudiante, en orden alfabético.
     */
    public function handle(Curso $curso, ?Estudiante $soloEstudiante = null): Collection
    {
        $umbral = (float) $curso->pnf->umbral_inasistencia_clases;

        $estudiantes = $soloEstudiante
            ? new EloquentCollection([$soloEstudiante])
            : $curso->estudiantes()->orderBy('apellidos')->orderBy('nombres')->get();

        $conteos = Asistencia::query()
            ->where('sesionable_type', (new SesionClase)->getMorphClass())
            ->whereIn('sesionable_id', $curso->sesiones()->realizadas()->select('id'))
            ->whereIn('estudiante_id', $estudiantes->modelKeys())
            ->selectRaw('estudiante_id, estado, count(*) as total')
            ->groupBy('estudiante_id', 'estado')
            ->toBase()
            ->get()
            ->groupBy('estudiante_id');

        return $estudiantes->mapWithKeys(function (Estudiante $estudiante) use ($conteos, $umbral): array {
            $porEstado = collect($conteos->get($estudiante->getKey(), []))
                ->mapWithKeys(fn (object $fila): array => [$fila->estado => (int) $fila->total]);

            $contar = fn (EstadoAsistencia $estado): int => $porEstado->get($estado->value, 0);
            $sesiones = $porEstado->sum();
            $ausentes = $porEstado
                ->filter(fn (int $total, string $estado): bool => EstadoAsistencia::from($estado)->esInasistencia())
                ->sum();
            $porcentaje = $sesiones > 0 ? round($ausentes * 100 / $sesiones, 2) : 0.0;

            return [$estudiante->getKey() => new ResumenInasistencia(
                estudiante: $estudiante,
                sesiones: $sesiones,
                presentes: $contar(EstadoAsistencia::Presente),
                tardanzas: $contar(EstadoAsistencia::Tardanza),
                ausentes: $ausentes,
                justificadas: $contar(EstadoAsistencia::Justificado),
                porcentaje: $porcentaje,
                nivel: NivelInasistencia::segun($porcentaje, $umbral),
            )];
        });
    }
}
