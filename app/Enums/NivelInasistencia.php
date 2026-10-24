<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Situación de un estudiante en un curso respecto al umbral de inasistencia del PNF.
 */
enum NivelInasistencia: string implements HasColor, HasLabel
{
    case Normal = 'normal';
    case EnRiesgo = 'en_riesgo';
    case Superado = 'superado';

    /**
     * Fracción del umbral a partir de la cual el estudiante está en riesgo.
     */
    public const FRACCION_RIESGO = 0.8;

    /**
     * Supera el umbral quien lo excede (con 25 %, el 25 % exacto aún es riesgo).
     */
    public static function segun(float $porcentaje, float $umbral): self
    {
        return match (true) {
            $porcentaje <= 0 => self::Normal,
            $porcentaje > $umbral => self::Superado,
            $porcentaje >= $umbral * self::FRACCION_RIESGO => self::EnRiesgo,
            default => self::Normal,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::EnRiesgo => 'En riesgo',
            self::Superado => 'Umbral superado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::EnRiesgo => 'warning',
            self::Superado => 'danger',
        };
    }
}
