<?php

namespace App\Filament\Admin\Resources\Sedes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                Section::make('Ubicación')
                    ->description('Para validar por GPS la asistencia con QR dinámico. Si se deja vacía, no se valida la ubicación.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('latitud')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->requiredWith('longitud'),
                        TextInput::make('longitud')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->requiredWith('latitud'),
                        TextInput::make('radio_metros')
                            ->label('Radio permitido (m)')
                            ->numeric()
                            ->integer()
                            ->minValue(20)
                            ->maxValue(5000)
                            ->placeholder((string) config('asistencia.gps.radio_metros')),
                    ]),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
