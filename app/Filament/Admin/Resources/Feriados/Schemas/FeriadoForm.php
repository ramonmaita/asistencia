<?php

namespace App\Filament\Admin\Resources\Feriados\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeriadoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->required(),
            ]);
    }
}
