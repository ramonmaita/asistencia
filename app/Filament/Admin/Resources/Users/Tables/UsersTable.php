<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\RolPnf;
use App\Filament\App\Support\AccionesDeCuenta;
use App\Models\Pnf;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('pnfs'))
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
                TextColumn::make('roles')
                    ->label('PNF y rol')
                    ->state(fn (User $record): array => $record->pnfs
                        ->map(fn (Pnf $pnf): string => "{$pnf->codigo} · {$pnf->pivot->rol->getLabel()}")
                        ->all())
                    ->badge(),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(),
                IconColumn::make('activo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options(RolPnf::class)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $rol) => $query->whereHas('pnfs', fn (Builder $pnfs) => $pnfs->where('pnf_user.rol', $rol)),
                    )),
                SelectFilter::make('pnfs')
                    ->label('PNF')
                    ->relationship('pnfs', 'nombre'),
                TernaryFilter::make('is_superadmin')
                    ->label('Superadmin'),
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    AccionesDeCuenta::cambiarContrasena(),
                    AccionesDeCuenta::activarODesactivar(),
                ]),
            ]);
    }
}
