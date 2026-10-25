<?php

namespace App\Models;

use App\Enums\Turno;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\SeccionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de estudiantes de un trayecto en un periodo académico.
 */
#[Fillable(['pnf_id', 'trayecto_id', 'periodo_id', 'sede_id', 'codigo', 'turno'])]
class Seccion extends Model
{
    /** @use HasFactory<SeccionFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'secciones';

    protected function casts(): array
    {
        return [
            'turno' => Turno::class,
        ];
    }

    public function trayecto(): BelongsTo
    {
        return $this->belongsTo(Trayecto::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class)->withTimestamps();
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    /**
     * Inscribe a los estudiantes en todas las unidades curriculares de la sección.
     *
     * @param  array<int, int>  $estudianteIds
     */
    public function inscribirEnSusCursos(array $estudianteIds): void
    {
        foreach ($this->cursos as $curso) {
            $curso->estudiantes()->syncWithoutDetaching($estudianteIds);
        }
    }

    /**
     * Retira a los estudiantes de las unidades curriculares de la sección
     * (su historial de asistencia se conserva).
     *
     * @param  array<int, int>  $estudianteIds
     */
    public function retirarDeSusCursos(array $estudianteIds): void
    {
        foreach ($this->cursos as $curso) {
            $curso->estudiantes()->detach($estudianteIds);
        }
    }
}
