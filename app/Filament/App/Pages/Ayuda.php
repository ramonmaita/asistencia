<?php

namespace App\Filament\App\Pages;

use App\Enums\RolPnf;
use App\Filament\App\Concerns\MuestraAyuda;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Guías del panel académico según el rol del usuario en el PNF actual
 * (el coordinador también ve la del docente).
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
        if (auth()->user()->is_superadmin) {
            return ['coordinador', 'docente', 'administrativo'];
        }

        return match (auth()->user()->rolEnPnfActual()) {
            RolPnf::Coordinador => ['coordinador', 'docente'],
            RolPnf::Administrativo => ['administrativo'],
            default => ['docente'],
        };
    }
}
