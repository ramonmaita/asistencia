<?php

namespace App\Filament\Estudiante\Pages;

use App\Filament\App\Concerns\MuestraAyuda;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Guía del estudiante.
 */
class Ayuda extends Page
{
    use MuestraAyuda;

    protected static ?string $title = 'Ayuda';

    protected static ?string $slug = 'ayuda';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    public function guiasDelUsuario(): array
    {
        return ['estudiante'];
    }
}
