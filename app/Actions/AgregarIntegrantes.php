<?php

namespace App\Actions;

use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agrega estudiantes a un proyecto. Deben estar inscritos en el curso de la
 * UC Proyecto, no pertenecer a otro proyecto del mismo curso y no superar el
 * máximo de integrantes del trayecto (1 en la modalidad individual).
 */
class AgregarIntegrantes
{
    /**
     * @param  array<int, int>  $estudianteIds
     *
     * @throws ValidationException
     */
    public function handle(Proyecto $proyecto, array $estudianteIds, string $campo = 'estudiantes'): void
    {
        $estudianteIds = array_values(array_unique(array_map('intval', $estudianteIds)));
        $curso = $proyecto->curso;
        $maximo = $proyecto->trayecto()->max_integrantes;

        $noInscritos = array_diff($estudianteIds, $curso->estudiantes()->pluck('estudiantes.id')->all());

        if ($noInscritos !== []) {
            throw ValidationException::withMessages([$campo => 'Solo pueden ser integrantes los estudiantes inscritos en el curso.']);
        }

        $enOtroProyecto = $curso->proyectos()
            ->whereKeyNot($proyecto->getKey())
            ->whereHas('integrantes', fn ($integrantes) => $integrantes->whereIn('estudiante_id', $estudianteIds))
            ->exists();

        if ($enOtroProyecto) {
            throw ValidationException::withMessages([$campo => 'Algún estudiante ya pertenece a otro proyecto de este curso.']);
        }

        $nuevos = array_diff($estudianteIds, $proyecto->integrantes()->pluck('estudiante_id')->all());

        if ($proyecto->integrantes()->count() + count($nuevos) > $maximo) {
            throw ValidationException::withMessages([$campo => "El proyecto admite como máximo {$maximo} ".($maximo === 1 ? 'integrante.' : 'integrantes.')]);
        }

        DB::transaction(function () use ($proyecto, $nuevos) {
            foreach ($nuevos as $estudianteId) {
                $proyecto->integrantes()->create([
                    'pnf_id' => $proyecto->pnf_id,
                    'curso_id' => $proyecto->curso_id,
                    'estudiante_id' => $estudianteId,
                ]);
            }
        });
    }
}
