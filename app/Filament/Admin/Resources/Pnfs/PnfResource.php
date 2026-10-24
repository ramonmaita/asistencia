<?php

namespace App\Filament\Admin\Resources\Pnfs;

use App\Filament\Admin\Resources\Pnfs\Pages\CreatePnf;
use App\Filament\Admin\Resources\Pnfs\Pages\EditPnf;
use App\Filament\Admin\Resources\Pnfs\Pages\ListPnfs;
use App\Filament\Admin\Resources\Pnfs\RelationManagers\UsersRelationManager;
use App\Filament\Admin\Resources\Pnfs\Schemas\PnfForm;
use App\Filament\Admin\Resources\Pnfs\Tables\PnfsTable;
use App\Models\Pnf;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PnfResource extends Resource
{
    protected static ?string $model = Pnf::class;

    protected static ?string $modelLabel = 'PNF';

    protected static ?string $pluralModelLabel = 'PNF';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    public static function form(Schema $schema): Schema
    {
        return PnfForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PnfsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPnfs::route('/'),
            'create' => CreatePnf::route('/create'),
            'edit' => EditPnf::route('/{record}/edit'),
        ];
    }
}
