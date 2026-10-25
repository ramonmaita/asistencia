<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Resultado del proyecto para cada integrante.
 */
enum VeredictoProyecto: string implements HasColor, HasLabel
{
    case Aprobado = 'aprobado';
    case Reprobado = 'reprobado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::Reprobado => 'Reprobado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aprobado => 'success',
            self::Reprobado => 'danger',
        };
    }
}
