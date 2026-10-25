<?php

namespace App\Filament\App\Resources\Proyectos\Tables;

use App\Models\Periodo;
use App\Models\Proyecto;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProyectosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['curso.unidadCurricular', 'curso.seccion.trayecto', 'curso.docentes'])
                ->withCount(['integrantes', 'tutorias as tutorias_realizadas_count' => fn (Builder $tutorias) => $tutorias->realizadas()]))
            ->columns([
                TextColumn::make('titulo')
                    ->label('Título')
                    ->description(fn (Proyecto $record): ?string => $record->comunidad)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('curso.seccion.codigo')
                    ->label('Sección')
                    ->description(fn (Proyecto $record): string => $record->curso->unidadCurricular->nombre),
                TextColumn::make('curso.docentes.name')
                    ->label('Tutores')
                    ->badge(),
                TextColumn::make('integrantes_count')
                    ->label('Integrantes')
                    ->formatStateUsing(fn (int $state, Proyecto $record): string => "{$state} / {$record->curso->seccion->trayecto->max_integrantes}")
                    ->color(fn (int $state, Proyecto $record): ?string => $state < $record->curso->seccion->trayecto->min_integrantes ? 'warning' : null),
                TextColumn::make('tutorias_realizadas_count')
                    ->label('Tutorías realizadas'),
            ])
            ->defaultSort('titulo')
            ->filters([
                SelectFilter::make('periodo')
                    ->relationship('curso.seccion.periodo', 'nombre')
                    ->default(fn (): ?int => Periodo::activo()->value('id')),
                SelectFilter::make('seccion')
                    ->label('Sección')
                    ->relationship('curso.seccion', 'codigo'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
