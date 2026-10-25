<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\EstadoSesion;
use App\Enums\TipoSesion;
use App\Models\Concerns\BelongsToPnf;
use App\Models\Contracts\Sesionable;
use Database\Factories\SesionClaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Una clase concreta de un curso (fecha y hora).
 */
#[Fillable(['pnf_id', 'curso_id', 'fecha', 'hora_inicio', 'hora_fin', 'estado', 'tipo', 'observacion'])]
class SesionClase extends Model implements Sesionable
{
    /** @use HasFactory<SesionClaseFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'sesiones_clase';

    protected $attributes = [
        'estado' => 'programada',
        'tipo' => 'regular',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoSesion::class,
            'tipo' => TipoSesion::class,
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function asistencias(): MorphMany
    {
        return $this->morphMany(Asistencia::class, 'sesionable');
    }

    public function scopeRealizadas(Builder $query): void
    {
        $query->where('estado', EstadoSesion::Realizada);
    }

    public function participantes(): BelongsToMany
    {
        return $this->curso->estudiantes();
    }

    public function responsables(): Collection
    {
        return $this->curso->docentes;
    }

    public function marcarRealizada(): void
    {
        if ($this->estado !== EstadoSesion::Realizada) {
            $this->update(['estado' => EstadoSesion::Realizada]);
        }
    }

    public function esGestionadaPor(User $user): bool
    {
        if ($user->esCoordinadorDe($this->curso->pnf)) {
            return true;
        }

        return $this->curso->docentes()->whereKey($user->getKey())->exists();
    }

    public function descripcion(): string
    {
        return "{$this->curso->unidadCurricular->nombre} · {$this->fechaLarga()} {$this->horario()}";
    }

    /**
     * Rango horario sin segundos, p. ej. "08:00–10:15".
     */
    public function horario(): string
    {
        return substr($this->hora_inicio, 0, 5).'–'.substr($this->hora_fin, 0, 5);
    }

    /**
     * Fecha con el día de la semana, p. ej. "Lunes 12/10/2026".
     */
    public function fechaLarga(): string
    {
        return DiaSemana::from($this->fecha->dayOfWeekIso)->getLabel().' '.$this->fecha->format('d/m/Y');
    }

    public function yaOcurrio(): bool
    {
        return $this->fecha->lte(today());
    }
}
