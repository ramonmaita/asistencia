<?php

namespace App\Filament\App\Resources\Trayectos\Tables;

use App\Enums\ModalidadProyecto;
use App\Models\Trayecto;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrayectosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('modalidad_proyecto')
                    ->label('Proyecto')
                    ->badge(),
                TextColumn::make('integrantes')
                    ->label('Integrantes')
                    ->state(fn (Trayecto $record): string => $record->modalidad_proyecto === ModalidadProyecto::Individual
                        ? '1'
                        : "{$record->min_integrantes} – {$record->max_integrantes}"),
                TextColumn::make('unidades_curriculares_count')
                    ->label('UC')
                    ->counts('unidadesCurriculares'),
                TextColumn::make('umbral_inasistencia_tutorias')
                    ->label('Umbral tutorías')
                    ->suffix('%'),
            ])
            ->defaultSort('numero')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
