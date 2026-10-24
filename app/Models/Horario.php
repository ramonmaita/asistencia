<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Database\Factories\HorarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque semanal de clase de un curso. Se accede siempre a través del curso,
 * por eso no lleva pnf_id propio.
 */
#[Fillable(['curso_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'aula'])]
class Horario extends Model
{
    /** @use HasFactory<HorarioFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
