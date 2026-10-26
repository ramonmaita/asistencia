<?php

namespace App\Filament\Admin\Pages;

use App\Filament\App\Concerns\MuestraAyuda;
use App\Support\ManualDeUsuario;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Manual completo: la guía del administrador y la de cada rol, para
 * orientar a quien lo necesite.
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
        return array_keys(ManualDeUsuario::GUIAS);
    }
}
