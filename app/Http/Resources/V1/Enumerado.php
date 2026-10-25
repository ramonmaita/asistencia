<?php

namespace App\Http\Resources\V1;

use Filament\Support\Contracts\HasLabel;

/**
 * Los enums viajan como {"valor": "...", "etiqueta": "..."} para que la app
 * muestre el texto en español sin duplicar las traducciones.
 */
final class Enumerado
{
    /**
     * @return array{valor: string, etiqueta: string}|null
     */
    public static function de(?HasLabel $enum): ?array
    {
        return $enum === null ? null : ['valor' => $enum->value, 'etiqueta' => $enum->getLabel()];
    }
}
