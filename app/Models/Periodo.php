<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'fecha_inicio', 'fecha_fin', 'activo'])]
class Periodo extends Model
{
    protected static function booted(): void
    {
        // Solo puede haber un periodo activo a la vez.
        static::saved(function (Periodo $periodo) {
            if ($periodo->activo) {
                static::whereKeyNot($periodo->getKey())->where('activo', true)->update(['activo' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function scopeActivo(Builder $query): void
    {
        $query->where('activo', true);
    }
}
