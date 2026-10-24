<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModalidadProyecto: string implements HasLabel
{
    case Individual = 'individual';
    case Grupal = 'grupal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Grupal => 'Grupal',
        };
    }
}
