<?php

namespace App\Filament\Admin\Resources\Sedes;

use App\Filament\Admin\Resources\Sedes\Pages\CreateSede;
use App\Filament\Admin\Resources\Sedes\Pages\EditSede;
use App\Filament\Admin\Resources\Sedes\Pages\ListSedes;
use App\Filament\Admin\Resources\Sedes\Schemas\SedeForm;
use App\Filament\Admin\Resources\Sedes\Tables\SedesTable;
use App\Models\Sede;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SedeResource extends Resource
{
    protected static ?string $model = Sede::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return SedeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SedesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSedes::route('/'),
            'create' => CreateSede::route('/create'),
            'edit' => EditSede::route('/{record}/edit'),
        ];
    }
}
