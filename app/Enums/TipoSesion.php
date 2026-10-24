<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoSesion: string implements HasLabel
{
    case Regular = 'regular';
    case Extra = 'extra';

    public function getLabel(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Extra => 'Extra',
        };
    }
}
