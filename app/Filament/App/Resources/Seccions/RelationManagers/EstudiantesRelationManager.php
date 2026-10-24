<?php

namespace App\Filament\App\Resources\Seccions\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Estudiantes inscritos en la sección. La lista de opciones solo incluye
 * estudiantes del PNF actual gracias al PnfScope.
 */
class EstudiantesRelationManager extends RelationManager
{
    protected static string $relationship = 'estudiantes';

    protected static ?string $title = 'Estudiantes inscritos';

    protected static ?string $modelLabel = 'estudiante';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record): string => "{$record->cedula} · {$record->nombre_completo}")
            ->columns([
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable(),
            ])
            ->defaultSort('apellidos')
            ->headerActions([
                AttachAction::make()
                    ->label('Inscribir estudiantes')
                    ->multiple()
                    ->recordSelectSearchColumns(['cedula', 'nombres', 'apellidos']),
            ])
            ->recordActions([
                DetachAction::make()->label('Retirar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Retirar seleccionados'),
                ]),
            ]);
    }
}
