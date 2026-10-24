<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Turno: string implements HasLabel
{
    case Manana = 'manana';
    case Tarde = 'tarde';
    case Noche = 'noche';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manana => 'Mañana',
            self::Tarde => 'Tarde',
            self::Noche => 'Noche',
        };
    }
}
