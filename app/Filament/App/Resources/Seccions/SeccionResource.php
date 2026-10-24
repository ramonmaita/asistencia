<?php

namespace App\Filament\App\Resources\Seccions;

use App\Filament\App\Resources\Seccions\Pages\CreateSeccion;
use App\Filament\App\Resources\Seccions\Pages\EditSeccion;
use App\Filament\App\Resources\Seccions\Pages\ListSeccions;
use App\Filament\App\Resources\Seccions\RelationManagers\EstudiantesRelationManager;
use App\Filament\App\Resources\Seccions\Schemas\SeccionForm;
use App\Filament\App\Resources\Seccions\Tables\SeccionsTable;
use App\Models\Seccion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SeccionResource extends Resource
{
    protected static ?string $model = Seccion::class;

    protected static ?string $slug = 'secciones';

    protected static ?string $tenantRelationshipName = 'secciones';

    protected static ?string $modelLabel = 'sección';

    protected static ?string $pluralModelLabel = 'secciones';

    protected static ?string $recordTitleAttribute = 'codigo';

    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return SeccionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeccionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EstudiantesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeccions::route('/'),
            'create' => CreateSeccion::route('/create'),
            'edit' => EditSeccion::route('/{record}/edit'),
        ];
    }
}
