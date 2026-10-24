<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPnf;
use Database\Factories\UnidadCurricularFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pnf_id', 'trayecto_id', 'codigo', 'nombre', 'horas_semanales', 'es_proyecto'])]
class UnidadCurricular extends Model
{
    /** @use HasFactory<UnidadCurricularFactory> */
    use BelongsToPnf, HasFactory;

    protected $table = 'unidades_curriculares';

    protected $attributes = [
        'es_proyecto' => false,
    ];

    protected function casts(): array
    {
        return [
            'horas_semanales' => 'integer',
            'es_proyecto' => 'boolean',
        ];
    }

    public function trayecto(): BelongsTo
    {
        return $this->belongsTo(Trayecto::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }
}
