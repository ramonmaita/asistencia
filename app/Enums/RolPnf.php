<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Rol del personal dentro de un PNF. El administrativo gestiona los datos
 * del PNF (estudiantes, personal, inscripciones, reportes y carnets), pero
 * no dicta clases ni registra asistencia.
 */
enum RolPnf: string implements HasColor, HasLabel
{
    case Coordinador = 'coordinador';
    case Docente = 'docente';
    case Administrativo = 'administrativo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coordinador => 'Coordinador',
            self::Docente => 'Docente',
            self::Administrativo => 'Administrativo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Coordinador => 'primary',
            self::Docente => 'gray',
            self::Administrativo => 'warning',
        };
    }
}
