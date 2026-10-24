<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoJustificacion;
use App\Enums\MetodoAsistencia;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\AsistenciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Asistencia de un estudiante a una sesión de clase (o, en la Fase 4, a una tutoría).
 */
#[Fillable(['pnf_id', 'sesionable_type', 'sesionable_id', 'estudiante_id', 'estado', 'metodo', 'registrado_por', 'registrado_at'])]
class Asistencia extends Model
{
    /** @use HasFactory<AsistenciaFactory> */
    use BelongsToPnf, HasFactory;

    protected $attributes = [
        'metodo' => 'manual',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoAsistencia::class,
            'metodo' => MetodoAsistencia::class,
            'registrado_at' => 'datetime',
        ];
    }

    public function sesionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function justificaciones(): HasMany
    {
        return $this->hasMany(Justificacion::class);
    }

    /**
     * Solo se justifica una ausencia que no tenga ya una solicitud pendiente o aprobada.
     */
    public function puedeJustificarse(): bool
    {
        return $this->estado === EstadoAsistencia::Ausente
            && ! $this->justificaciones()
                ->whereIn('estado', [EstadoJustificacion::Pendiente, EstadoJustificacion::Aprobada])
                ->exists();
    }
}
