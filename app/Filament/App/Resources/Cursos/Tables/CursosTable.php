<?php

namespace App\Filament\App\Resources\Cursos\Tables;

use App\Models\Curso;
use App\Models\Periodo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CursosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['seccion.trayecto', 'seccion.periodo', 'unidadCurricular', 'docentes', 'horarios']))
            ->columns([
                TextColumn::make('unidadCurricular.nombre')
                    ->label('Unidad curricular')
                    ->description(fn (Curso $record): string => $record->unidadCurricular->codigo)
                    ->searchable(),
                IconColumn::make('unidadCurricular.es_proyecto')
                    ->label('Proyecto')
                    ->boolean(),
                TextColumn::make('seccion.codigo')
                    ->label('Sección')
                    ->description(fn (Curso $record): string => $record->seccion->trayecto->nombre)
                    ->searchable(),
                TextColumn::make('docentes.name')
                    ->label('Docentes')
                    ->badge(),
                TextColumn::make('horario')
                    ->state(fn (Curso $record): array => $record->horarios
                        ->map(fn ($bloque): string => $bloque->dia_semana->getLabel().' '.substr($bloque->hora_inicio, 0, 5).'–'.substr($bloque->hora_fin, 0, 5))
                        ->all())
                    ->listWithLineBreaks(),
            ])
            ->filters([
                SelectFilter::make('periodo')
                    ->relationship('seccion.periodo', 'nombre')
                    ->default(fn (): ?int => Periodo::activo()->value('id')),
                SelectFilter::make('seccion')
                    ->relationship('seccion', 'codigo'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
