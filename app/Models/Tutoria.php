<?php

namespace App\Models;

use App\Enums\DiaSemana;
use App\Enums\EstadoSesion;
use App\Models\Concerns\BelongsToPnf;
use App\Models\Contracts\Sesionable;
use Database\Factories\TutoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reunión del tutor con los integrantes de un proyecto.
 */
#[Fillable(['pnf_id', 'proyecto_id', 'fecha', 'hora_inicio', 'hora_fin', 'tema', 'estado', 'observacion'])]
class Tutoria extends Model implements Sesionable
{
    /** @use HasFactory<TutoriaFactory> */
    use BelongsToPnf, HasFactory;

    protected $attributes = [
        'estado' => 'programada',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoSesion::class,
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
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
        return $this->proyecto->estudiantes();
    }

    /**
     * Tutores académicos y, si lo hay, el técnico.
     */
    public function responsables(): Collection
    {
        return $this->proyecto->curso->docentes->push($this->proyecto->tutorTecnico)->filter()->unique('id')->values();
    }

    public function esGestionadaPor(User $user): bool
    {
        return $this->proyecto->esTutoradoPor($user);
    }

    public function descripcion(): string
    {
        return "Tutoría · {$this->proyecto->titulo} · {$this->fechaLarga()} {$this->horario()}";
    }

    public function horario(): string
    {
        return substr($this->hora_inicio, 0, 5).'–'.substr($this->hora_fin, 0, 5);
    }

    public function fechaLarga(): string
    {
        return DiaSemana::from($this->fecha->dayOfWeekIso)->getLabel().' '.$this->fecha->format('d/m/Y');
    }

    public function inicio(): Carbon
    {
        return $this->fecha->copy()->setTimeFromTimeString($this->hora_inicio);
    }

    public function fin(): Carbon
    {
        return $this->fecha->copy()->setTimeFromTimeString($this->hora_fin);
    }

    /**
     * Las tutorías no tienen sede: no se valida la ubicación.
     */
    public function ubicacion(): ?Sede
    {
        return null;
    }

    public function yaOcurrio(): bool
    {
        return $this->fecha->lte(today());
    }

    public function marcarRealizada(): void
    {
        if ($this->estado !== EstadoSesion::Realizada) {
            $this->update(['estado' => EstadoSesion::Realizada]);
        }
    }
}
