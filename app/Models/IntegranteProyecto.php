<?php

namespace App\Models;

use App\Enums\VeredictoProyecto;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\IntegranteProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estudiante integrante de un proyecto, con su veredicto individual.
 */
#[Fillable(['pnf_id', 'proyecto_id', 'curso_id', 'estudiante_id'])]
class IntegranteProyecto extends Model
{
    /** @use HasFactory<IntegranteProyectoFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'integrantes_proyecto';

    protected function casts(): array
    {
        return [
            'veredicto' => VeredictoProyecto::class,
            'veredicto_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function autorDelVeredicto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veredicto_por');
    }

    public function registrarVeredicto(VeredictoProyecto $veredicto, User $tutor, ?string $observacion = null): void
    {
        $this->forceFill([
            'veredicto' => $veredicto,
            'observacion_veredicto' => $observacion,
            'veredicto_por' => $tutor->getKey(),
            'veredicto_at' => now(),
        ])->save();
    }
}
