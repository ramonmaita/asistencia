<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoJustificacion;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\JustificacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Solicitud del estudiante para justificar una inasistencia, con su soporte.
 * El soporte se guarda en el disco privado "local".
 */
#[Fillable(['pnf_id', 'asistencia_id', 'estudiante_id', 'motivo', 'soporte_path', 'soporte_nombre'])]
class Justificacion extends Model
{
    /** @use HasFactory<JustificacionFactory> */
    use BelongsToPnf, HasFactory;

    public const DISCO = 'local';

    public const DIRECTORIO = 'justificaciones';

    /** Tamaño máximo del soporte, en KB. */
    public const TAMANO_MAXIMO = 5120;

    public const TIPOS_PERMITIDOS = ['application/pdf', 'image/jpeg', 'image/png'];

    protected $table = 'justificaciones';

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoJustificacion::class,
            'revisada_at' => 'datetime',
        ];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', EstadoJustificacion::Pendiente);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === EstadoJustificacion::Pendiente;
    }

    /**
     * Aprueba la solicitud y deja la asistencia como "justificado".
     */
    public function aprobar(User $revisor, ?string $observacion = null): void
    {
        DB::transaction(function () use ($revisor, $observacion) {
            $this->revisar(EstadoJustificacion::Aprobada, $revisor, $observacion);
            $this->asistencia->update(['estado' => EstadoAsistencia::Justificado]);
        });
    }

    /**
     * Rechaza la solicitud; la asistencia sigue como "ausente".
     */
    public function rechazar(User $revisor, ?string $observacion = null): void
    {
        $this->revisar(EstadoJustificacion::Rechazada, $revisor, $observacion);
    }

    private function revisar(EstadoJustificacion $estado, User $revisor, ?string $observacion): void
    {
        if (! $this->estaPendiente()) {
            throw new LogicException('La justificación ya fue revisada.');
        }

        $this->forceFill([
            'estado' => $estado,
            'revisada_por' => $revisor->getKey(),
            'revisada_at' => now(),
            'observacion_revision' => $observacion,
        ])->save();
    }
}
