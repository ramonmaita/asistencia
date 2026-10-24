<?php

namespace App\Console\Commands;

use App\Actions\CalcularInasistencias;
use App\Enums\NivelInasistencia;
use App\Models\AvisoInasistencia;
use App\Models\Curso;
use App\Notifications\NivelInasistenciaAlcanzado;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

/**
 * Revisa los cursos del periodo activo y avisa (base de datos y correo) al
 * estudiante, a los docentes del curso y a los coordinadores del PNF cuando
 * un estudiante pasa a "en riesgo" o supera el umbral. Cada nivel se avisa
 * una sola vez por estudiante y curso (ver avisos_inasistencia).
 */
#[Signature('asistencias:notificar-inasistencias')]
#[Description('Avisa cuando un estudiante pasa a "en riesgo" o supera el umbral de inasistencia')]
class NotificarInasistencias extends Command
{
    public function handle(CalcularInasistencias $calcular): int
    {
        $enviados = 0;

        Curso::query()
            ->delPeriodoActivo()
            ->whereHas('pnf', fn (Builder $pnfs) => $pnfs->where('activo', true))
            ->with(['pnf.coordinadores', 'docentes', 'unidadCurricular', 'seccion'])
            ->each(function (Curso $curso) use ($calcular, &$enviados): void {
                $avisados = AvisoInasistencia::query()
                    ->whereBelongsTo($curso)
                    ->get()
                    ->groupBy('estudiante_id');

                foreach ($calcular->handle($curso) as $estudianteId => $resumen) {
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
                        'nivel' => $resumen->nivel,
                        'porcentaje' => $resumen->porcentaje,
                    ]);

                    $personal = $curso->docentes->merge($curso->pnf->coordinadores)->unique('id');

                    Notification::send(
                        $personal->push($resumen->estudiante),
                        new NivelInasistenciaAlcanzado($curso, $resumen->estudiante, $resumen->nivel, $resumen->porcentaje),
                    );

                    $enviados++;
                }
            });

        $this->info("Avisos de inasistencia enviados: {$enviados}.");

        return self::SUCCESS;
    }
}
