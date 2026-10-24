<?php

namespace App\Filament\Admin\Resources\Feriados;

use App\Filament\Admin\Resources\Feriados\Pages\CreateFeriado;
use App\Filament\Admin\Resources\Feriados\Pages\EditFeriado;
use App\Filament\Admin\Resources\Feriados\Pages\ListFeriados;
use App\Filament\Admin\Resources\Feriados\Schemas\FeriadoForm;
use App\Filament\Admin\Resources\Feriados\Tables\FeriadosTable;
use App\Models\Feriado;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeriadoResource extends Resource
{
    protected static ?string $model = Feriado::class;

    protected static string|UnitEnum|null $navigationGroup = 'Calendario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    public static function form(Schema $schema): Schema
    {
        return FeriadoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeriadosTable::configure($table);
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
            'index' => ListFeriados::route('/'),
            'create' => CreateFeriado::route('/create'),
            'edit' => EditFeriado::route('/{record}/edit'),
        ];
    }
}
