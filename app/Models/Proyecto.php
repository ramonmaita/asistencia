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
 * Proyecto de un curso de la UC Proyecto. Tiene tres tutores:
 *  - académico: los docentes del curso de la UC Proyecto;
 *  - técnico: otro docente del PNF, que acompaña las tutorías;
 *  - institucional: persona externa a la universidad, sin acceso al sistema.
 */
#[Fillable(['pnf_id', 'curso_id', 'titulo', 'descripcion', 'comunidad', 'tutor_tecnico_id', 'tutor_institucional', 'tutor_institucional_contacto'])]
class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use BelongsToPnf, HasFactory;

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function tutorTecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_tecnico_id');
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

    /**
     * Proyectos en los que el docente es tutor académico o técnico.
     */
    public function scopeTutoradosPor(Builder $query, User $docente): void
    {
        $query->where(fn (Builder $proyectos) => $proyectos
            ->whereHas('curso', fn (Builder $cursos) => $cursos->dictadosPor($docente))
            ->orWhere('tutor_tecnico_id', $docente->getKey()));
    }

    public function scopeDelPeriodoActivo(Builder $query): void
    {
        $query->whereHas('curso', fn (Builder $cursos) => $cursos->delPeriodoActivo());
    }

    public function scopeConIntegrante(Builder $query, Estudiante $estudiante): void
    {
        $query->whereHas('integrantes', fn (Builder $integrantes) => $integrantes->whereBelongsTo($estudiante));
    }

    /**
     * El tutor académico (o el coordinador) gestiona integrantes y veredictos.
     */
    public function esTutorAcademico(User $user): bool
    {
        return $user->esCoordinadorDe($this->curso->pnf)
            || $this->curso->docentes()->whereKey($user->getKey())->exists();
    }

    /**
     * Los tutores académico y técnico ven el proyecto y registran tutorías.
     */
    public function esTutoradoPor(User $user): bool
    {
        return $this->esTutorAcademico($user) || $this->tutor_tecnico_id === $user->getKey();
    }

    /**
     * Indica si al proyecto todavía le faltan integrantes según el trayecto.
     */
    public function estaIncompleto(): bool
    {
        return $this->integrantes()->count() < $this->trayecto()->min_integrantes;
    }
}
