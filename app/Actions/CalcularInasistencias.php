<?php

namespace App\Actions;

use App\Enums\EstadoAsistencia;
use App\Enums\NivelInasistencia;
use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Proyecto;
use App\Models\SesionClase;
use App\Models\Tutoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Calcula el porcentaje de inasistencia de cada estudiante, por separado
 * para las clases de un curso y para las tutorías de un proyecto.
 *
 * Solo cuentan las sesiones realizadas en las que el estudiante tiene
 * asistencia registrada. Porcentaje = ausencias sin justificar / sesiones
 * registradas. El nivel compara ese porcentaje con el umbral que
 * corresponde: el del PNF para clases y el del trayecto para tutorías.
 */
class CalcularInasistencias
{
    /**
     * @return Collection<int, ResumenInasistencia> Resúmenes por id de estudiante, en orden alfabético.
     */
    public function handle(Curso $curso, ?Estudiante $soloEstudiante = null): Collection
    {
        return $this->resumir(
            $soloEstudiante ? new EloquentCollection([$soloEstudiante]) : $curso->estudiantes()->orderBy('apellidos')->orderBy('nombres')->get(),
            (new SesionClase)->getMorphClass(),
            $curso->sesiones()->realizadas()->select('id'),
            (float) $curso->pnf->umbral_inasistencia_clases,
        );
    }

    /**
     * Inasistencia a las tutorías del proyecto de cada integrante.
     *
     * @return Collection<int, ResumenInasistencia> Resúmenes por id de estudiante, en orden alfabético.
     */
    public function deProyecto(Proyecto $proyecto, ?Estudiante $soloEstudiante = null): Collection
    {
        return $this->resumir(
            $soloEstudiante ? new EloquentCollection([$soloEstudiante]) : $proyecto->estudiantes()->orderBy('apellidos')->orderBy('nombres')->get(),
            (new Tutoria)->getMorphClass(),
            $proyecto->tutorias()->realizadas()->select('id'),
            (float) $proyecto->trayecto()->umbral_inasistencia_tutorias,
        );
    }

    /**
     * @param  EloquentCollection<int, Estudiante>  $estudiantes
     * @param  Builder|Relation  $sesionesRealizadas  Consulta de los ids de las sesiones realizadas.
     * @return Collection<int, ResumenInasistencia>
     */
    private function resumir(EloquentCollection $estudiantes, string $tipoDeSesion, Builder|Relation $sesionesRealizadas, float $umbral): Collection
    {
        $conteos = Asistencia::query()
            ->where('sesionable_type', $tipoDeSesion)
            ->whereIn('sesionable_id', $sesionesRealizadas)
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
