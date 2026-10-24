<?php

namespace App\Filament\Admin\Resources\Pnfs\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PnfForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('slug')
                    ->helperText('Identificador en la URL del panel. Si se deja vacío se genera a partir del código.')
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('umbral_inasistencia_clases')
                    ->label('Umbral de inasistencia en clases')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(25),
                FileUpload::make('logo')
                    ->image()
                    ->directory('pnfs/logos'),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
