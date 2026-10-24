<?php

namespace App\Filament\App\Resources\Seccions\Tables;

use App\Models\Periodo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeccionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['trayecto', 'periodo', 'sede']))
            ->columns([
                TextColumn::make('codigo')
                    ->label('Sección')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trayecto.nombre')
                    ->sortable(),
                TextColumn::make('periodo.nombre'),
                TextColumn::make('turno')
                    ->badge(),
                TextColumn::make('sede.nombre'),
                TextColumn::make('estudiantes_count')
                    ->label('Estudiantes')
                    ->counts('estudiantes'),
                TextColumn::make('cursos_count')
                    ->label('Cursos')
                    ->counts('cursos'),
            ])
            ->defaultSort('codigo')
            ->filters([
                SelectFilter::make('periodo')
                    ->relationship('periodo', 'nombre')
                    ->default(fn (): ?int => Periodo::activo()->value('id')),
                SelectFilter::make('trayecto')
                    ->relationship('trayecto', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
