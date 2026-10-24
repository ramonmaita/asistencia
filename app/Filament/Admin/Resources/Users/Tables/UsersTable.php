<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('pnfs.codigo')
                    ->label('PNF')
                    ->badge(),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
