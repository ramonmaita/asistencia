<?php

namespace App\Actions;

use App\Enums\EstadoAsistencia;
use App\Enums\MetodoAsistencia;
use App\Models\Asistencia;
use App\Models\Contracts\Sesionable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Guarda la asistencia de los estudiantes a una sesión de clase o a una
 * tutoría y la marca como realizada. Sirve para el registro manual y, en la
 * Fase 5, para el registro por QR (un solo estudiante con otro método).
 *
 * Solo se actualizan los registros cuyo estado cambia, de modo que el método,
 * el autor y la hora de los demás se conservan.
 */
class RegistrarAsistencias
{
    /**
     * @param  array<int, EstadoAsistencia>  $estados  Estado por id de estudiante.
     */
    public function handle(Sesionable $sesion, array $estados, ?User $registrador, MetodoAsistencia $metodo = MetodoAsistencia::Manual): void
    {
        $inscritos = $sesion->participantes()->pluck('estudiantes.id')->all();
        $ajenos = array_diff(array_keys($estados), $inscritos);

        if ($ajenos !== []) {
            throw new InvalidArgumentException('Hay estudiantes que no están convocados a esta sesión.');
        }

        DB::transaction(function () use ($sesion, $estados, $registrador, $metodo) {
            $actuales = $sesion->asistencias()->get()->keyBy('estudiante_id');

            foreach ($estados as $estudianteId => $estado) {
                /** @var Asistencia|null $asistencia */
                $asistencia = $actuales->get($estudianteId);

                if ($asistencia?->estado === $estado) {
                    continue;
                }

                $sesion->asistencias()->updateOrCreate(
                    ['estudiante_id' => $estudianteId],
                    [
                        'pnf_id' => $sesion->pnf_id,
                        'estado' => $estado,
                        'metodo' => $metodo,
                        'registrado_por' => $registrador?->getKey(),
                        'registrado_at' => now(),
                    ],
                );
            }

            $sesion->marcarRealizada();
        });
    }

    /**
     * Estado inicial para el formulario: el ya registrado o "presente".
     *
     * @return array<int, EstadoAsistencia>
     */
    public function estadosIniciales(Sesionable $sesion): array
    {
        $registrados = $sesion->asistencias()->pluck('estado', 'estudiante_id');

        return $sesion->participantes()
            ->pluck('estudiantes.id')
            ->mapWithKeys(fn (int $id): array => [$id => $registrados->get($id, EstadoAsistencia::Presente)])
            ->all();
    }
}
