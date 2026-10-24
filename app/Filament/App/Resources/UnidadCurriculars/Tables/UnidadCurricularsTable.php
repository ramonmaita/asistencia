<?php

namespace App\Filament\App\Resources\UnidadCurriculars\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnidadCurricularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('trayecto'))
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trayecto.nombre')
                    ->sortable(),
                TextColumn::make('horas_semanales')
                    ->label('Horas'),
                IconColumn::make('es_proyecto')
                    ->label('Proyecto')
                    ->boolean(),
            ])
            ->defaultSort('codigo')
            ->filters([
                SelectFilter::make('trayecto')
                    ->relationship('trayecto', 'nombre'),
                TernaryFilter::make('es_proyecto')
                    ->label('Proyecto'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
