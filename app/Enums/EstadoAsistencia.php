<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoAsistencia: string implements HasColor, HasLabel
{
    case Presente = 'presente';
    case Ausente = 'ausente';
    case Tardanza = 'tardanza';
    case Justificado = 'justificado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Ausente => 'Ausente',
            self::Tardanza => 'Tardanza',
            self::Justificado => 'Justificado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Presente => 'success',
            self::Ausente => 'danger',
            self::Tardanza => 'warning',
            self::Justificado => 'info',
        };
    }

    /**
     * Solo la ausencia sin justificar cuenta para el porcentaje de inasistencia;
     * la tardanza cuenta como asistencia.
     */
    public function esInasistencia(): bool
    {
        return $this === self::Ausente;
    }
}
