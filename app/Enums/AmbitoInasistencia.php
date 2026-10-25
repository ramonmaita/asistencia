<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * La asistencia a clases y a las tutorías del proyecto se controlan por
 * separado, cada una con su umbral.
 */
enum AmbitoInasistencia: string implements HasLabel
{
    case Clases = 'clases';
    case Tutorias = 'tutorias';

    public function getLabel(): string
    {
        return match ($this) {
            self::Clases => 'Clases',
            self::Tutorias => 'Tutorías',
        };
    }
}
