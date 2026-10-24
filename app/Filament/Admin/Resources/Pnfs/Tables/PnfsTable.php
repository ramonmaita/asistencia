<?php

namespace App\Filament\Admin\Resources\Pnfs\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PnfsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Docentes')
                    ->counts('users'),
                TextColumn::make('estudiantes_count')
                    ->label('Estudiantes')
                    ->counts('estudiantes'),
                TextColumn::make('umbral_inasistencia_clases')
                    ->label('Umbral inasistencia')
                    ->suffix('%'),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('nombre')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
