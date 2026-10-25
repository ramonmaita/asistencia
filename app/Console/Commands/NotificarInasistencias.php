<?php

namespace App\Console\Commands;

use App\Actions\CalcularInasistencias;
use App\Actions\ResumenInasistencia;
use App\Enums\AmbitoInasistencia;
use App\Enums\NivelInasistencia;
use App\Models\AvisoInasistencia;
use App\Models\Curso;
use App\Models\Proyecto;
use App\Models\User;
use App\Notifications\NivelInasistenciaAlcanzado;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Revisa los cursos y proyectos del periodo activo y avisa (base de datos y
 * correo) al estudiante, a los docentes del curso (o a los tutores académico
 * y técnico del proyecto) y a los coordinadores del PNF cuando un estudiante
 * pasa a "en riesgo" o supera el umbral, por separado para clases y para
 * tutorías. Cada nivel se avisa una sola vez
 * por estudiante, curso y ámbito (ver avisos_inasistencia).
 */
#[Signature('asistencias:notificar-inasistencias')]
#[Description('Avisa cuando un estudiante pasa a "en riesgo" o supera el umbral de inasistencia a clases o tutorías')]
class NotificarInasistencias extends Command
{
    private int $enviados = 0;

    public function handle(CalcularInasistencias $calcular): int
    {
        $this->enviados = 0;
        $pnfActivo = fn (Builder $pnfs) => $pnfs->where('activo', true);

        Curso::query()
            ->delPeriodoActivo()
            ->whereHas('pnf', $pnfActivo)
            ->with(['pnf.coordinadores', 'docentes', 'unidadCurricular', 'seccion'])
            ->each(fn (Curso $curso) => $this->avisar($curso, $calcular->handle($curso), AmbitoInasistencia::Clases, $curso->docentes));

        Proyecto::query()
            ->delPeriodoActivo()
            ->whereHas('pnf', $pnfActivo)
            ->with(['curso.pnf.coordinadores', 'curso.docentes', 'curso.unidadCurricular', 'curso.seccion.trayecto', 'tutorTecnico'])
            ->each(fn (Proyecto $proyecto) => $this->avisar(
                $proyecto->curso,
                $calcular->deProyecto($proyecto),
                AmbitoInasistencia::Tutorias,
                $proyecto->curso->docentes->push($proyecto->tutorTecnico)->filter(),
                $proyecto,
            ));

        $this->info("Avisos de inasistencia enviados: {$this->enviados}.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, ResumenInasistencia>  $resumenes
     * @param  Collection<int, User>  $docentes  Docentes a cargo (del curso o tutores del proyecto).
     */
    private function avisar(Curso $curso, Collection $resumenes, AmbitoInasistencia $ambito, Collection $docentes, ?Proyecto $proyecto = null): void
    {
        $avisados = AvisoInasistencia::query()
            ->whereBelongsTo($curso)
            ->where('ambito', $ambito)
            ->get()
            ->groupBy('estudiante_id');

        foreach ($resumenes as $estudianteId => $resumen) {
            if ($resumen->nivel === NivelInasistencia::Normal || ! $resumen->estudiante->activo) {
                continue;
            }

            $nivelesAvisados = $avisados->get($estudianteId, collect())->pluck('nivel');

            // Quien ya fue avisado de un nivel igual o mayor no recibe otro aviso.
            if ($nivelesAvisados->contains($resumen->nivel) || $nivelesAvisados->contains(NivelInasistencia::Superado)) {
                continue;
            }

            AvisoInasistencia::create([
                'pnf_id' => $curso->pnf_id,
                'curso_id' => $curso->getKey(),
                'estudiante_id' => $estudianteId,
                'ambito' => $ambito,
                'nivel' => $resumen->nivel,
                'porcentaje' => $resumen->porcentaje,
            ]);

            $personal = $docentes->merge($curso->pnf->coordinadores)->unique('id');

            Notification::send(
                $personal->push($resumen->estudiante),
                new NivelInasistenciaAlcanzado($curso, $resumen->estudiante, $resumen->nivel, $resumen->porcentaje, $proyecto),
            );

            $this->enviados++;
        }
    }
}
