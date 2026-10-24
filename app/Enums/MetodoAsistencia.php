<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MetodoAsistencia: string implements HasLabel
{
    case Manual = 'manual';
    case QrDocente = 'qr_docente';
    case QrDinamico = 'qr_dinamico';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::QrDocente => 'QR del carnet',
            self::QrDinamico => 'QR dinámico',
        };
    }
}
