<?php

namespace App\Filament\App\Resources\UnidadCurriculars;

use App\Filament\App\Resources\UnidadCurriculars\Pages\CreateUnidadCurricular;
use App\Filament\App\Resources\UnidadCurriculars\Pages\EditUnidadCurricular;
use App\Filament\App\Resources\UnidadCurriculars\Pages\ListUnidadCurriculars;
use App\Filament\App\Resources\UnidadCurriculars\Schemas\UnidadCurricularForm;
use App\Filament\App\Resources\UnidadCurriculars\Tables\UnidadCurricularsTable;
use App\Models\UnidadCurricular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnidadCurricularResource extends Resource
{
    protected static ?string $model = UnidadCurricular::class;

    protected static ?string $slug = 'unidades-curriculares';

    protected static ?string $tenantRelationshipName = 'unidadesCurriculares';

    protected static ?string $modelLabel = 'unidad curricular';

    protected static ?string $pluralModelLabel = 'unidades curriculares';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string|UnitEnum|null $navigationGroup = 'Plan de estudios';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function form(Schema $schema): Schema
    {
        return UnidadCurricularForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnidadCurricularsTable::configure($table);
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
            'index' => ListUnidadCurriculars::route('/'),
            'create' => CreateUnidadCurricular::route('/create'),
            'edit' => EditUnidadCurricular::route('/{record}/edit'),
        ];
    }
}
