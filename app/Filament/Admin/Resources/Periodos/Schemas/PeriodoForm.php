<?php

namespace App\Filament\Admin\Resources\Periodos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PeriodoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->placeholder('2026-I')
                    ->required()
                    ->unique(ignoreRecord: true),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->required()
                    ->after('fecha_inicio'),
                Toggle::make('activo')
                    ->label('Periodo activo')
                    ->helperText('Al activarlo se desactiva el periodo anterior.'),
            ]);
    }
}
