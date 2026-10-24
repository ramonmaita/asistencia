<?php

namespace App\Filament\Admin\Resources\Pnfs\RelationManagers;

use App\Enums\RolPnf;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Docentes y coordinadores asignados al PNF (tabla pnf_user).
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Docentes y coordinadores';

    protected static ?string $modelLabel = 'miembro';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('rol')
                    ->options(RolPnf::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('cedula')
                    ->label('Cédula')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo'),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('rol')
                    ->options(RolPnf::class),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Asignar docente')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'cedula', 'email'])
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('rol')
                            ->options(RolPnf::class)
                            ->default(RolPnf::Docente)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Cambiar rol'),
                DetachAction::make()->label('Quitar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
