<?php

namespace App\Models;

use App\Enums\ModalidadProyecto;
use App\Models\Concerns\BelongsToPnf;
use Database\Factories\TrayectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pnf_id', 'numero', 'nombre', 'modalidad_proyecto', 'min_integrantes', 'max_integrantes', 'umbral_inasistencia_tutorias'])]
class Trayecto extends Model
{
    /** @use HasFactory<TrayectoFactory> */
    use BelongsToPnf, HasFactory;

    protected $attributes = [
        'modalidad_proyecto' => 'grupal',
        'min_integrantes' => 1,
        'max_integrantes' => 1,
        'umbral_inasistencia_tutorias' => 25,
    ];

    protected static function booted(): void
    {
        static::saving(function (Trayecto $trayecto) {
            if ($trayecto->modalidad_proyecto === ModalidadProyecto::Individual) {
                $trayecto->min_integrantes = 1;
                $trayecto->max_integrantes = 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'modalidad_proyecto' => ModalidadProyecto::class,
            'min_integrantes' => 'integer',
            'max_integrantes' => 'integer',
            'umbral_inasistencia_tutorias' => 'decimal:2',
        ];
    }

    public function unidadesCurriculares(): HasMany
    {
        return $this->hasMany(UnidadCurricular::class);
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class);
    }
}
