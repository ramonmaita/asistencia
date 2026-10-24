<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RolPnf: string implements HasLabel
{
    case Coordinador = 'coordinador';
    case Docente = 'docente';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coordinador => 'Coordinador',
            self::Docente => 'Docente',
        };
    }
}
