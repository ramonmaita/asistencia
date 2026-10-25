<?php

namespace App\Models;

use App\Enums\AmbitoInasistencia;
use App\Enums\NivelInasistencia;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\AvisoInasistenciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aviso ya enviado de que un estudiante llegó a un nivel de inasistencia en
 * un curso, a sus clases o a las tutorías de su proyecto.
 */
#[Fillable(['pnf_id', 'curso_id', 'estudiante_id', 'ambito', 'nivel', 'porcentaje'])]
class AvisoInasistencia extends Model
{
    /** @use HasFactory<AvisoInasistenciaFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'avisos_inasistencia';

    protected $attributes = [
        'ambito' => 'clases',
    ];

    protected function casts(): array
    {
        return [
            'ambito' => AmbitoInasistencia::class,
            'nivel' => NivelInasistencia::class,
            'porcentaje' => 'decimal:2',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class);
    }
}
