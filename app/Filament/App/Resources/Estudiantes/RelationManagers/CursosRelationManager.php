<?php

namespace App\Filament\App\Resources\Estudiantes\RelationManagers;

use App\Models\Curso;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unidades curriculares que cursa el estudiante. Desde aquí se ajusta la
 * inscripción de quien no cursa todas las de su sección.
 */
class CursosRelationManager extends RelationManager
{
    protected static string $relationship = 'cursos';

    protected static ?string $inverseRelationship = 'estudiantes';

    protected static ?string $title = 'Unidades curriculares inscritas';

    protected static ?string $modelLabel = 'unidad curricular';

    protected static ?string $pluralModelLabel = 'unidades curriculares';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unidadCurricular', 'seccion.periodo']))
            ->recordTitle(fn (Curso $record): string => "{$record->unidadCurricular->nombre} · {$record->seccion->codigo}")
            ->columns([
                TextColumn::make('unidadCurricular.nombre')
                    ->label('Unidad curricular')
                    ->description(fn (Curso $record): string => $record->unidadCurricular->codigo),
                TextColumn::make('seccion.codigo')
                    ->label('Sección'),
                TextColumn::make('seccion.periodo.nombre')
                    ->label('Periodo'),
            ])
            ->filters([
                SelectFilter::make('periodo')
                    ->relationship('seccion.periodo', 'nombre'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Inscribir en unidad curricular')
                    ->modalHeading('Inscribir en unidades curriculares')
                    ->modalSubmitActionLabel('Inscribir')
                    ->attachAnother(false)
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query
                        ->delPeriodoActivo()
                        ->with(['unidadCurricular', 'seccion'])),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Retirar')
                    ->modalDescription('Su historial de asistencia en esta unidad curricular se conserva.'),
            ]);
    }
}
