<?php

namespace App\Filament\Admin\Resources\Sedes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SedeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('direccion')
                    ->label('Dirección'),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
