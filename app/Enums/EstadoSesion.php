<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoSesion: string implements HasColor, HasLabel
{
    case Programada = 'programada';
    case Realizada = 'realizada';
    case Suspendida = 'suspendida';

    public function getLabel(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Realizada => 'Realizada',
            self::Suspendida => 'Suspendida',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Programada => 'gray',
            self::Realizada => 'success',
            self::Suspendida => 'warning',
        };
    }
}
