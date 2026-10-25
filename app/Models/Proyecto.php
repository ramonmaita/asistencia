<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPnf;
use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Proyecto de un curso de la UC Proyecto. Sus tutores académicos son los
 * docentes del curso; el tutor comunitario es externo y no usa el sistema.
 */
#[Fillable(['pnf_id', 'curso_id', 'titulo', 'descripcion', 'comunidad', 'tutor_comunitario', 'tutor_comunitario_contacto'])]
class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use BelongsToPnf, HasFactory;

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function integrantes(): HasMany
    {
        return $this->hasMany(IntegranteProyecto::class);
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'integrantes_proyecto')->withTimestamps();
    }

    public function tutorias(): HasMany
    {
        return $this->hasMany(Tutoria::class);
    }

    /**
     * Asistencias a las tutorías del proyecto.
     */
    public function asistencias(): HasManyThrough
    {
        return $this->hasManyThrough(Asistencia::class, Tutoria::class, 'proyecto_id', 'sesionable_id')
            ->where('asistencias.sesionable_type', (new Tutoria)->getMorphClass());
    }

    public function trayecto(): Trayecto
    {
        return $this->curso->seccion->trayecto;
    }

    public function scopeTutoradosPor(Builder $query, User $docente): void
    {
        $query->whereHas('curso', fn (Builder $cursos) => $cursos->dictadosPor($docente));
    }

    public function scopeDelPeriodoActivo(Builder $query): void
    {
        $query->whereHas('curso', fn (Builder $cursos) => $cursos->delPeriodoActivo());
    }

    public function scopeConIntegrante(Builder $query, Estudiante $estudiante): void
    {
        $query->whereHas('integrantes', fn (Builder $integrantes) => $integrantes->whereBelongsTo($estudiante));
    }

    public function esTutoradoPor(User $user): bool
    {
        return $user->esCoordinadorDe($this->curso->pnf)
            || $this->curso->docentes()->whereKey($user->getKey())->exists();
    }

    /**
     * Indica si al proyecto todavía le faltan integrantes según el trayecto.
     */
    public function estaIncompleto(): bool
    {
        return $this->integrantes()->count() < $this->trayecto()->min_integrantes;
    }
}
