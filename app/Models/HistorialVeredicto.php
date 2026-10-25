<?php

namespace App\Models;

use App\Enums\VeredictoProyecto;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\HistorialVeredictoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de un veredicto (o de su cambio) para un integrante del proyecto.
 */
#[Fillable(['pnf_id', 'integrante_proyecto_id', 'veredicto_anterior', 'veredicto', 'observacion', 'registrado_por'])]
class HistorialVeredicto extends Model
{
    /** @use HasFactory<HistorialVeredictoFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'historial_veredictos';

    protected function casts(): array
    {
        return [
            'veredicto_anterior' => VeredictoProyecto::class,
            'veredicto' => VeredictoProyecto::class,
        ];
    }

    public function integrante(): BelongsTo
    {
        return $this->belongsTo(IntegranteProyecto::class, 'integrante_proyecto_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
