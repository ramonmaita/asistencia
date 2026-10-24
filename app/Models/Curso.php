<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPnf;
use Database\Factories\CursoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unidad curricular dictada en una sección: tiene docentes y horario,
 * y sus estudiantes son los inscritos en la sección.
 */
#[Fillable(['pnf_id', 'seccion_id', 'unidad_curricular_id'])]
class Curso extends Model
{
    /** @use HasFactory<CursoFactory> */
    use BelongsToPnf, HasFactory;

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function unidadCurricular(): BelongsTo
    {
        return $this->belongsTo(UnidadCurricular::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function scopeDictadosPor(Builder $query, User $docente): void
    {
        $query->whereHas('docentes', fn (Builder $docentes) => $docentes->whereKey($docente->getKey()));
    }
}
