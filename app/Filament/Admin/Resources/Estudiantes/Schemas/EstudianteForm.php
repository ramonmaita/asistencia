<?php

namespace App\Filament\Admin\Resources\Estudiantes\Schemas;

use App\Filament\App\Resources\Estudiantes\Schemas\EstudianteForm as EstudianteFormDelPnf;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EstudianteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('pnf_id')
                    ->label('PNF')
                    ->relationship('pnf', 'nombre')
                    ->required()
                    ->columnSpanFull(),
                ...EstudianteFormDelPnf::campos(),
            ]);
    }
}
