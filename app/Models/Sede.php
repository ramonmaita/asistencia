<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'direccion', 'latitud', 'longitud', 'radio_metros', 'activo'])]
class Sede extends Model
{
    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
            'radio_metros' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    public function radio(): int
    {
        return $this->radio_metros ?? (int) config('asistencia.gps.radio_metros');
    }

    /**
     * Distancia en metros hasta un punto (fórmula del haversine).
     */
    public function distanciaA(float $latitud, float $longitud): float
    {
        $radioTierra = 6371000;
        $dLat = deg2rad($latitud - $this->latitud);
        $dLon = deg2rad($longitud - $this->longitud);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->latitud)) * cos(deg2rad($latitud)) * sin($dLon / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
