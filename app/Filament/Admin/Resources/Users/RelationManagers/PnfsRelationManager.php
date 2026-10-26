<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\RolPnf;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * PNF en los que trabaja el usuario y su rol en cada uno (tabla pnf_user).
 */
class PnfsRelationManager extends RelationManager
{
    protected static string $relationship = 'pnfs';

    protected static ?string $title = 'PNF y roles';

    protected static ?string $modelLabel = 'PNF';

    protected static ?string $pluralModelLabel = 'PNF';

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
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código'),
                TextColumn::make('nombre'),
                TextColumn::make('rol')
                    ->label('Rol')
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Asignar a un PNF')
                    ->preloadRecordSelect()
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
            ]);
    }
}
