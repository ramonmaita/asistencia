<?php

namespace App\Filament\App\Resources\Trayectos;

use App\Filament\App\Resources\Trayectos\Pages\CreateTrayecto;
use App\Filament\App\Resources\Trayectos\Pages\EditTrayecto;
use App\Filament\App\Resources\Trayectos\Pages\ListTrayectos;
use App\Filament\App\Resources\Trayectos\Schemas\TrayectoForm;
use App\Filament\App\Resources\Trayectos\Tables\TrayectosTable;
use App\Models\Trayecto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TrayectoResource extends Resource
{
    protected static ?string $model = Trayecto::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string|UnitEnum|null $navigationGroup = 'Plan de estudios';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function form(Schema $schema): Schema
    {
        return TrayectoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrayectosTable::configure($table);
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
            'index' => ListTrayectos::route('/'),
            'create' => CreateTrayecto::route('/create'),
            'edit' => EditTrayecto::route('/{record}/edit'),
        ];
    }
}
