<?php

namespace App\Actions;

use App\Enums\TipoSesion;
use App\Models\Curso;
use App\Models\Feriado;
use App\Models\Horario;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Crea las sesiones regulares de un curso para todo el periodo de su sección,
 * una por cada bloque del horario semanal, saltando los feriados.
 *
 * Es idempotente: no duplica las sesiones que ya existen (misma fecha y hora
 * de inicio) ni toca su estado, así que puede ejecutarse de nuevo tras
 * agregar bloques al horario o feriados.
 */
class GenerarSesionesClase
{
    /**
     * @return int Cantidad de sesiones creadas.
     */
    public function handle(Curso $curso): int
    {
        $curso->loadMissing(['seccion.periodo', 'horarios']);
        $periodo = $curso->seccion->periodo;

        if ($curso->horarios->isEmpty()) {
            return 0;
        }

        $feriados = Feriado::pluck('fecha')
            ->map(fn (Carbon $fecha): string => $fecha->toDateString())
            ->flip();

        $existentes = $curso->sesiones()
            ->get(['fecha', 'hora_inicio'])
            ->map(fn ($sesion): string => $sesion->fecha->toDateString().' '.$this->normalizarHora($sesion->hora_inicio))
            ->flip();

        $nuevas = [];

        foreach (CarbonPeriod::create($periodo->fecha_inicio, $periodo->fecha_fin) as $dia) {
            $fecha = $dia->toDateString();

            if ($feriados->has($fecha)) {
                continue;
            }

            foreach ($curso->horarios->filter(fn (Horario $bloque): bool => $bloque->dia_semana->value === $dia->dayOfWeekIso) as $bloque) {
                $horaInicio = $this->normalizarHora($bloque->hora_inicio);

                if ($existentes->has("{$fecha} {$horaInicio}")) {
                    continue;
                }

                $nuevas[] = [
                    'pnf_id' => $curso->pnf_id,
                    'fecha' => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $this->normalizarHora($bloque->hora_fin),
                    'tipo' => TipoSesion::Regular,
                ];
            }
        }

        DB::transaction(function () use ($curso, $nuevas) {
            foreach ($nuevas as $sesion) {
                $curso->sesiones()->create($sesion);
            }
        });

        return count($nuevas);
    }

    private function normalizarHora(string $hora): string
    {
        return Carbon::parse($hora)->format('H:i:s');
    }
}
