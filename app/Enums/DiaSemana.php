<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Días de la semana según ISO-8601 (1 = lunes), igual que Carbon::dayOfWeekIso.
 */
enum DiaSemana: int implements HasLabel
{
    case Lunes = 1;
    case Martes = 2;
    case Miercoles = 3;
    case Jueves = 4;
    case Viernes = 5;
    case Sabado = 6;
    case Domingo = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miércoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sábado',
            self::Domingo => 'Domingo',
        };
    }
}
