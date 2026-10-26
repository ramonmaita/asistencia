<?php

namespace App\Filament\Admin\Resources\Estudiantes\Tables;

use App\Filament\App\Resources\Estudiantes\Tables\EstudiantesTable as EstudiantesTableDelPnf;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EstudiantesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('pnf'))
            ->columns([
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('apellidos')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->searchable(),
                TextColumn::make('pnf.codigo')
                    ->label('PNF')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('debe_cambiar_password')
                    ->label('Clave inicial')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('apellidos')
            ->filters([
                SelectFilter::make('pnf')
                    ->label('PNF')
                    ->relationship('pnf', 'nombre'),
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                EditAction::make(),
                EstudiantesTableDelPnf::accionesDeCuenta(),
            ]);
    }
}
