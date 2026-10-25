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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Unidad curricular dictada en una sección: tiene docentes, horario y
 * sus propios estudiantes inscritos.
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

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionClase::class);
    }

    /**
     * Estudiantes inscritos en esta unidad curricular (no necesariamente
     * todos los de la sección).
     */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class)->withTimestamps();
    }

    /**
     * Asistencias a las sesiones de clase del curso (no incluye tutorías).
     */
    public function asistencias(): HasManyThrough
    {
        return $this->hasManyThrough(Asistencia::class, SesionClase::class, 'curso_id', 'sesionable_id')
            ->where('asistencias.sesionable_type', (new SesionClase)->getMorphClass());
    }

    public function scopeDelPeriodoActivo(Builder $query): void
    {
        $query->whereHas('seccion.periodo', fn (Builder $periodos) => $periodos->where('activo', true));
    }

    public function scopeCursadosPor(Builder $query, Estudiante $estudiante): void
    {
        $query->whereHas('estudiantes', fn (Builder $estudiantes) => $estudiantes->whereKey($estudiante->getKey()));
    }

    public function scopeDictadosPor(Builder $query, User $docente): void
    {
        $query->whereHas('docentes', fn (Builder $docentes) => $docentes->whereKey($docente->getKey()));
    }
}
